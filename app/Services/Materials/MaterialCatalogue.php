<?php

namespace App\Services\Materials;

use App\Models\Material;
use App\Models\MaterialPrice;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Throwable;

/**
 * Reads the store's public catalogue: the sitemap lists every product page with its last change, and each page carries
 * the product's data (variants, prices, stock, bulk tiers, collections) in its embedded Next.js payload.
 * Only pages changed since the last run are fetched unless $all is set.
 */
class MaterialCatalogue
{
    private const UA = 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/126.0 Safari/537.36';

    /** @return array{seen:int, fetched:int, saved:int, failed:int, delisted:int} */
    public function sync(bool $all = false, ?int $limit = null, ?callable $log = null): array
    {
        $log ??= fn () => null;
        $base = rtrim(config('materials.source.base'), '/');
        $pages = $this->sitemap($base);
        $known = Material::pluck('sitemap_lastmod', 'handle');
        $todo = $pages->filter(fn ($lastmod, $handle) => $all || !isset($known[$handle]) || !$known[$handle] || $known[$handle]->lt($lastmod));
        if ($limit) $todo = $todo->take($limit);
        $log("{$pages->count()} products in sitemap, {$todo->count()} to read.");

        $saved = $failed = 0;
        foreach ($todo as $handle => $lastmod) {
            try {
                $html = Http::withHeaders(['User-Agent' => self::UA])->timeout(40)->retry(2, 2000)->get("$base/products/$handle")->throw()->body();
                $product = $this->product($html, $handle);
                if (!$product) { $failed++; $log("  no product data: $handle"); continue; }
                $this->save($product, $lastmod);
                $this->rememberDeliveryPins($html);
                $saved++;
                if ($saved % 50 === 0) $log("  $saved saved");
            } catch (Throwable $e) {
                $failed++;
                $log("  failed $handle: " . Str::limit($e->getMessage(), 120));
            }
            usleep(config('materials.source.delay_ms') * 1000);
        }
        // Products the store no longer lists stay for their price history but leave the catalogue.
        $delisted = $limit ? 0 : Material::where('is_listed', true)->whereNotIn('handle', $pages->keys())->update(['is_listed' => false]);
        Cache::forget('materials:stats');
        Cache::forget('materials:calc-lines');
        Cache::forget('materials:collections');
        return ['seen' => $pages->count(), 'fetched' => $todo->count(), 'saved' => $saved, 'failed' => $failed, 'delisted' => $delisted];
    }

    /** @return \Illuminate\Support\Collection<string, Carbon> handle => lastmod */
    public function sitemap(string $base)
    {
        $xml = Http::withHeaders(['User-Agent' => self::UA])->timeout(60)->retry(2, 3000)->get("$base/sitemap.xml")->throw()->body();
        preg_match_all('~<loc>\s*' . preg_quote($base, '~') . '/products/([^<\s]+)\s*</loc>\s*(?:<lastmod>([^<]+)</lastmod>)?~', $xml, $m, PREG_SET_ORDER);
        return collect($m)->mapWithKeys(fn ($r) => [urldecode($r[1]) => !empty($r[2]) ? Carbon::parse($r[2]) : now()]);
    }

    /** The product object for $handle from the page's embedded payload. */
    public function product(string $html, string $handle): ?array
    {
        $text = $this->payload($html);
        $at = strpos($text, '"handle":' . json_encode($handle, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
        if ($at === false) return null;
        // Walk back to the enclosing product object and read it whole.
        for ($start = strrpos(substr($text, 0, $at), '{"id":"gid://shopify/Product/'); $start !== false; $start = strrpos(substr($text, 0, $start), '{"id":"gid://shopify/Product/')) {
            $json = $this->objectAt($text, $start);
            $data = $json ? json_decode($json, true) : null;
            if (($data['handle'] ?? null) === $handle && isset($data['variants'])) {
                $data['_description'] = $this->resolveText($text, $data['descriptionHtml'] ?? null);
                return $data;
            }
            if ($start === 0) break;
        }
        return null;
    }

    /** Long text is sent as a separate row ("38:T<hex byte length>,<text>") and referenced as "$38". */
    private function resolveText(string $text, ?string $value): ?string
    {
        if (!$value || !preg_match('/^\$([0-9a-f]+)$/', $value, $ref)) return $value;
        // A row starts at a chunk boundary or after a newline; the text before it can end in any character.
        preg_match_all('/' . $ref[1] . ':T([0-9a-f]+),/', $text, $all, PREG_OFFSET_CAPTURE | PREG_SET_ORDER);
        foreach ($all as $m) {
            $at = $m[0][1];
            if ($at === 0 || isset($this->rowStarts[$at]) || $text[$at - 1] === "\n") return substr($text, $at + strlen($m[0][0]), hexdec($m[1][0]));
        }
        return null;
    }

    /** Offsets in the joined payload where each pushed chunk begins. */
    private array $rowStarts = [];

    /** Removes sentences that name the source store or its delivery promises: the site sells under its own name. */
    public static function scrub(?string $text): ?string
    {
        if (!$text) return $text;
        $name = preg_quote(config('materials.source.name'), '~');
        $text = preg_replace('~[^.!?<>]*(?:\b' . $name . '\b|home-run\.co|60[- ]min(?:ute)?s? delivery)[^.!?<>]*[.!?]?~iu', '', $text);
        $text = preg_replace(['~<(li|p|h[2-4]|strong|b)>\s*</\1>~', '~[ \t]{2,}~'], ['', ' '], $text);
        return trim($text) ?: null;
    }

    /** Keeps headings, paragraphs, lists, tables and emphasis; drops scripts, styles, comments, links and every attribute. */
    public static function sanitize(?string $html): ?string
    {
        if (!$html || !trim(strip_tags($html))) return null;
        $allowed = ['p', 'br', 'ul', 'ol', 'li', 'strong', 'b', 'em', 'i', 'h2', 'h3', 'h4', 'table', 'thead', 'tbody', 'tr', 'td', 'th'];
        $doc = new \DOMDocument();
        libxml_use_internal_errors(true);
        $doc->loadHTML('<?xml encoding="utf-8"?><div id="root">' . $html . '</div>', LIBXML_NONET);
        libxml_clear_errors();
        $root = $doc->getElementById('root');
        if (!$root) return null;
        $clean = function (\DOMNode $node) use (&$clean, $allowed, $doc) {
            foreach (iterator_to_array($node->childNodes) as $child) {
                if ($child instanceof \DOMComment || in_array(strtolower($child->nodeName), ['script', 'style', 'iframe', 'object', 'svg', 'img', 'form'], true)) { $node->removeChild($child); continue; }
                if (!$child instanceof \DOMElement) continue;
                $clean($child);
                $tag = strtolower($child->nodeName);
                if (in_array($tag, $allowed, true)) {
                    while ($child->attributes->length) $child->removeAttribute($child->attributes->item(0)->nodeName);
                    continue;
                }
                // Unknown element (div, span, a…): keep its children, drop the element.
                $block = in_array($tag, ['h1', 'h5', 'h6'], true);
                $frag = $doc->createDocumentFragment();
                while ($child->firstChild) $frag->appendChild($child->firstChild);
                if ($block) { $p = $doc->createElement($tag === 'h1' ? 'h3' : 'h4'); $p->appendChild($frag); $node->replaceChild($p, $child); }
                else $node->replaceChild($frag, $child);
            }
        };
        $clean($root);
        $out = '';
        foreach ($root->childNodes as $c) $out .= $doc->saveHTML($c);
        $out = preg_replace(['~<p>\s*</p>~', '~(<br>\s*){3,}~'], ['', '<br><br>'], $out);
        return trim($out) ?: null;
    }

    private function payload(string $html): string
    {
        preg_match_all('~self\.__next_f\.push\(\[1,"(.*?)"\]\)</script>~s', $html, $m);
        $text = '';
        $this->rowStarts = [];
        foreach ($m[1] as $chunk) {
            $this->rowStarts[strlen($text)] = true;
            $text .= json_decode('"' . $chunk . '"') ?? '';
        }
        return $text;
    }

    /** The balanced JSON object starting at $start (string-aware brace matching). */
    private function objectAt(string $s, int $start): ?string
    {
        $depth = 0; $inString = false; $len = strlen($s);
        for ($i = $start; $i < $len; $i++) {
            $c = $s[$i];
            if ($inString) {
                if ($c === '\\') $i++;
                elseif ($c === '"') $inString = false;
                continue;
            }
            if ($c === '"') $inString = true;
            elseif ($c === '{') $depth++;
            elseif ($c === '}' && --$depth === 0) return substr($s, $start, $i - $start + 1);
        }
        return null;
    }

    private function save(array $p, Carbon $lastmod): void
    {
        $variants = collect($p['variants']['nodes'] ?? [])->map(function ($v) use ($p) {
            [$size, $unit] = self::packSize($v['title'] !== 'Default Title' ? $v['title'] : $p['title']) ?? self::packSize($p['title']) ?? [null, null];
            return ['title' => $v['title'] === 'Default Title' ? null : $v['title'], 'price' => (float) ($v['price']['amount'] ?? 0),
                'mrp' => isset($v['compareAtPrice']['amount']) ? (float) $v['compareAtPrice']['amount'] : null,
                'size' => $size, 'unit' => $unit, 'qty' => $v['quantityAvailable'] ?? null, 'available' => (bool) ($v['availableForSale'] ?? false)];
        })->filter(fn ($v) => $v['price'] > 0)->values();
        $pick = $variants->where('available', true)->sortBy('price')->first() ?? $variants->sortBy('price')->first();
        $collections = collect($p['collections']['nodes'] ?? [])->pluck('handle')->values()->all();
        $category = collect(array_keys(config('materials.categories')))->first(fn ($c) => in_array($c, $collections, true));
        $tiers = collect(json_decode($p['bulkPricingMetafield']['value'] ?? '[]', true) ?: [])
            ->filter(fn ($t) => ($t['quantity'] ?? 0) > 1 && ($t['price'] ?? 0) > 0)->map(fn ($t) => ['qty' => (int) $t['quantity'], 'price' => (float) $t['price']])->sortBy('qty')->values()->all();
        $brand = trim((string) ($p['brandMetafield']['value'] ?? '')) ?: null;
        $image = $p['images']['nodes'][0]['url'] ?? ($p['variants']['nodes'][0]['image']['url'] ?? null);

        $material = Material::updateOrCreate(['handle' => $p['handle']], [
            'title' => Str::limit(trim($p['title']), 250, ''), 'brand' => $brand, 'brand_slug' => $brand ? Str::slug($brand) : null,
            'product_type' => $p['productType'] ?: null, 'category' => $category, 'collections' => $collections, 'tags' => $p['tags'] ?? [],
            'price' => $pick['price'] ?? null, 'mrp' => $pick['mrp'] ?? null, 'unit_size' => $pick['size'] ?? null, 'unit' => $pick['unit'] ?? null,
            'in_stock' => $variants->contains('available', true), 'quantity_available' => $variants->sum(fn ($v) => max(0, (int) $v['qty'])) ?: null,
            'variants' => $variants->all(), 'bulk_tiers' => $tiers, 'image_url' => $image ? Str::limit($image, 495, '') : null,
            'images' => collect($p['images']['nodes'] ?? [])->pluck('url')->filter()->unique()->take(12)->values()->all(),
            'description' => self::scrub(self::sanitize($p['_description'] ?? null)),
            'summary' => Str::limit((string) self::scrub(trim((string) ($p['seo']['description'] ?? ''))), 495, '') ?: null, 'is_listed' => true,
            'source_created_at' => isset($p['createdAt']) ? Carbon::parse($p['createdAt']) : null,
            'source_updated_at' => isset($p['updatedAt']) ? Carbon::parse($p['updatedAt']) : null, 'sitemap_lastmod' => $lastmod,
        ]);
        if ($material->price) MaterialPrice::updateOrCreate(['material_id' => $material->id, 'recorded_on' => today()->toDateString()], ['price' => $material->price, 'mrp' => $material->mrp]);
    }

    /** "20L", "50 Kg Bag", "90 Meters / 1.5 Sqmm", "500 ml" → [size, kg|L|m]. */
    public static function packSize(string $text): ?array
    {
        if (!preg_match('~(\d+(?:\.\d+)?)\s*(kgs?|kilograms?|ltrs?|litres?|liters?|l|ml|gms?|g|grams?|mtrs?|meters?|metres?|m)(?![a-z])(?!\s*(?:capacity|load|wide|width|length|long))~i', $text, $m)) return null;
        $n = (float) $m[1]; $u = strtolower($m[2]);
        if ($n <= 0) return null;
        return match (true) {
            str_starts_with($u, 'k') => [$n, 'kg'],
            $u === 'ml' => [$n / 1000, 'L'],
            in_array($u, ['g', 'gm', 'gms', 'gram', 'grams'], true) => [$n / 1000, 'kg'],
            $u === 'l' || str_starts_with($u, 'lit') || str_starts_with($u, 'ltr') => [$n, 'L'],
            default => [$n, 'm'],
        };
    }

    /** The store's list of PIN codes it serves in 60 minutes, kept for the delivery check. */
    private function rememberDeliveryPins(string $html): void
    {
        if (Cache::has('materials:pins:fresh') || !preg_match('~const pincode60\s*=\s*(\[[^\]]*\])~', $html, $m)) return;
        $pins = array_values(array_filter(json_decode($m[1], true) ?: [], fn ($p) => preg_match('/^\d{6}$/', (string) $p)));
        if (!$pins) return;
        Cache::forever('materials:pins', $pins);
        Cache::put('materials:pins:fresh', true, now()->addHours(12));
    }
}
