<?php

namespace App\Services\Branding;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Throwable;

// Company, stock and fund-house logos: fetched once per website domain, stored on the public disk, served locally.
class Logos
{
    // Official domains of fund houses (AMFI names); unknown houses keep their initials badge.
    public const AMC_DOMAINS = [
        '360 one' => '360.one', 'aditya birla' => 'adityabirlacapital.com', 'angel one' => 'angelone.in', 'axis' => 'axismf.com', 'bajaj finserv' => 'bajajfinserv.in',
        'bandhan' => 'bandhanmutual.com', 'bank of india' => 'boimf.in', 'baroda bnp' => 'barodabnpparibasmf.in', 'canara robeco' => 'canararobeco.com', 'capitalmind' => 'capitalmind.in',
        'choice' => 'choiceindia.com', 'dsp' => 'dspim.com', 'edelweiss' => 'edelweissmf.com', 'franklin' => 'franklintempletonindia.com', 'groww' => 'groww.in', 'hdfc' => 'hdfcfund.com',
        'helios' => 'heliosmf.in', 'hsbc' => 'hsbc.co.in', 'icici' => 'icicipruamc.com', 'invesco' => 'invescomutualfund.com', 'iti' => 'itiamc.com', 'jio blackrock' => 'jioblackrockamc.com',
        'jm financial' => 'jmfinancialmf.com', 'kotak' => 'kotakmf.com', 'lic' => 'licmf.com', 'mahindra manulife' => 'mahindramanulife.com', 'mirae' => 'miraeassetmf.co.in',
        'motilal' => 'motilaloswalmf.com', 'navi' => 'navi.com', 'nippon' => 'nipponindiamf.com', 'nj ' => 'njmutualfund.com', 'old bridge' => 'oldbridgemf.com', 'pgim' => 'pgimindiamf.com',
        'ppfas' => 'ppfas.com', 'parag parikh' => 'ppfas.com', 'quantum' => 'quantumamc.com', 'quant ' => 'quantmutual.com', 'samco' => 'samcomf.com', 'sbi' => 'sbimf.com', 'shriram' => 'shriramamc.in',
        'sundaram' => 'sundarammutual.com', 'tata' => 'tatamutualfund.com', 'taurus' => 'taurusmutualfund.com', 'trust' => 'trustmf.com', 'union' => 'unionmf.com', 'uti' => 'utimf.com',
        'whiteoak' => 'whiteoakamc.com', 'zerodha' => 'zerodhafundhouse.com',
    ];

    public static function domain(?string $url): ?string
    {
        if (!$url || !trim($url)) return null;
        $host = parse_url(str_contains($url, '://') ? $url : 'https://' . trim($url), PHP_URL_HOST);
        $host = $host ? preg_replace('/^www\./', '', strtolower($host)) : null;
        return $host && str_contains($host, '.') && strlen($host) <= 190 ? $host : null;
    }

    public static function amcDomain(?string $name): ?string
    {
        $n = ' ' . strtolower((string) $name) . ' ';
        foreach (self::AMC_DOMAINS as $key => $domain) if (str_contains($n, ' ' . $key)) return $domain;
        return null;
    }

    /** Public URL of a stored logo, or null so callers show their initials badge. */
    public static function url(?string $domain): ?string
    {
        if (!$domain) return null;
        $map = Cache::remember('brand-logos:map', 600, fn () => rescue(fn () => DB::table('brand_logos')->where('status', 'ok')->pluck('path', 'domain')->all(), [], false));
        return isset($map[$domain]) ? asset('storage/' . $map[$domain]) : null;
    }

    /**
     * Downloads the logo for a domain; returns true when a usable image was stored. Tries the site's own
     * large icons first (apple-touch-icon / sized icons in its HTML), then the Google and DuckDuckGo favicon
     * services. Raster images under 32px are rejected — a blurry 16px favicon looks worse than the initials badge.
     */
    public function fetch(string $domain, ?string $name = null): bool
    {
        // logo.dev (optional, LOGO_DEV_TOKEN in .env) has high-resolution logos for most listed companies.
        $token = config('services.logo_dev.token');
        $sources = array_merge($token ? ['https://img.logo.dev/' . rawurlencode($domain) . '?token=' . rawurlencode($token) . '&size=128&format=png'] : [],
            $name ? $this->wikiLogo($name, $domain) : [], $this->siteIcons($domain), ['https://www.google.com/s2/favicons?domain=' . rawurlencode($domain) . '&sz=128', 'https://icons.duckduckgo.com/ip3/' . rawurlencode($domain) . '.ico']);
        foreach (array_slice(array_unique($sources), 0, 7) as $src) {
            try {
                $r = Http::timeout(10)->withHeaders(['User-Agent' => 'Mozilla/5.0 (compatible; logo fetch)'])->get($src);
                if (!$r->successful() || strlen($r->body()) < 150 || strlen($r->body()) > 600000) continue;
                $body = $r->body();
                // SVGs are served from our own domain, so reject anything that could run script.
                if (str_contains(substr($body, 0, 500), '<svg')) {
                    if (preg_match('/<script|javascript:|<foreignObject|<iframe|<!ENTITY|<embed|<object|\bon[a-z]+\s*=/i', $body)) continue;
                    $ext = 'svg';
                } else {
                    $info = @getimagesizefromstring($body);
                    $width = $info ? max($info[0], str_starts_with($body, "\0\0\1\0") ? $this->icoLargest($body) : 0) : 0;
                    if ($width < 32) continue;
                    $ext = ['image/png' => 'png', 'image/jpeg' => 'jpg', 'image/gif' => 'gif', 'image/webp' => 'webp', 'image/vnd.microsoft.icon' => 'ico', 'image/x-icon' => 'ico'][$info['mime']] ?? null;
                    if (!$ext) continue;
                }
                $path = 'logos/' . preg_replace('/[^a-z0-9.-]/', '', $domain) . '.' . $ext;
                Storage::disk('public')->put($path, $r->body());
                DB::table('brand_logos')->updateOrInsert(['domain' => $domain], ['path' => $path, 'status' => 'ok', 'checked_at' => now()]);
                return true;
            } catch (Throwable) {
                continue;
            }
        }
        DB::table('brand_logos')->updateOrInsert(['domain' => $domain], ['path' => null, 'status' => 'missing', 'checked_at' => now()]);
        return false;
    }

    /** Large icons a site declares in its homepage HTML, biggest first, plus the conventional apple-touch-icon path. */
    private function siteIcons(string $domain): array
    {
        $found = [];
        try {
            $get = fn ($url) => Http::timeout(8)->withHeaders(['User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/130.0 Safari/537.36', 'Accept' => 'text/html'])->get($url);
            try { $r = $get('https://' . $domain); } catch (Throwable) { $r = null; }
            if ((!$r || !$r->successful()) && !str_starts_with($domain, 'www.')) $r = $get('https://www.' . $domain);
            $base = (string) ($r->effectiveUri() ?? 'https://' . $domain);
            if ($r->successful() && preg_match_all('/<link\b[^>]*>/i', substr($r->body(), 0, 200000), $tags)) {
                foreach ($tags[0] as $tag) {
                    if (!preg_match('/rel=["\']?([^"\'>]+)/i', $tag, $rel) || !preg_match('/(apple-touch-icon|icon)/i', $rel[1])) continue;
                    if (!preg_match('/href=["\']?([^"\'\s>]+)/i', $tag, $href)) continue;
                    $size = preg_match('/sizes=["\']?(\d+)/i', $tag, $s) ? (int) $s[1] : (str_contains(strtolower($rel[1]), 'apple') ? 180 : (str_ends_with(strtolower($href[1]), '.svg') ? 150 : 16));
                    if ($size >= 32) $found[$this->absolute(html_entity_decode($href[1]), $base)] = $size;
                }
            }
        } catch (Throwable) {
        }
        arsort($found);
        return array_merge(array_keys($found), ['https://' . $domain . '/apple-touch-icon.png']);
    }

    /**
     * Logo from Wikidata (P154) or the Wikipedia infobox image, only for the entity whose official website (P856)
     * is this domain, so a namesake company never lends its logo.
     */
    private function wikiLogo(string $name, string $domain): array
    {
        $ua = ['User-Agent' => config('app.name') . ' logo sync (' . config('app.url') . ')'];
        try {
            $name = trim(preg_replace('/\b(limited|ltd|pvt|private|mutual fund)\b\.?/i', '', $name));
            $ids = collect(Http::timeout(8)->withHeaders($ua)->get('https://www.wikidata.org/w/api.php', ['action' => 'wbsearchentities', 'search' => $name, 'language' => 'en', 'type' => 'item', 'limit' => 5, 'format' => 'json'])->json('search') ?? [])->pluck('id')->filter()->all();
            if (!$ids) return [];
            $entities = Http::timeout(8)->withHeaders($ua)->get('https://www.wikidata.org/w/api.php', ['action' => 'wbgetentities', 'ids' => implode('|', $ids), 'props' => 'claims|sitelinks', 'format' => 'json'])->json('entities') ?? [];
            foreach ($ids as $id) {
                $e = $entities[$id] ?? null;
                $sites = collect($e['claims']['P856'] ?? [])->map(fn ($c) => self::domain($c['mainsnak']['datavalue']['value'] ?? null))->filter();
                if (!$sites->contains(fn ($s) => $s === $domain || str_ends_with($s, '.' . $domain) || str_ends_with($domain, '.' . $s))) continue;
                $out = [];
                if ($file = $e['claims']['P154'][0]['mainsnak']['datavalue']['value'] ?? null) $out[] = 'https://commons.wikimedia.org/wiki/Special:FilePath/' . rawurlencode(str_replace(' ', '_', $file)) . '?width=200';
                if ($title = $e['sitelinks']['enwiki']['title'] ?? null) {
                    $page = collect(Http::timeout(8)->withHeaders($ua)->get('https://en.wikipedia.org/w/api.php', ['action' => 'query', 'prop' => 'pageimages', 'piprop' => 'thumbnail|name', 'pithumbsize' => 200, 'titles' => $title, 'format' => 'json', 'formatversion' => 2])->json('query.pages') ?? [])->first();
                    // Lead images are often office photos; only take ones named as a logo.
                    if ($page && stripos($page['pageimage'] ?? '', 'logo') !== false && !empty($page['thumbnail']['source'])) $out[] = $page['thumbnail']['source'];
                }
                return $out;
            }
        } catch (Throwable) {
        }
        return [];
    }

    // Largest image width inside a .ico file (directory entries: 1 byte width, 0 means 256).
    private function icoLargest(string $ico): int
    {
        $count = unpack('v', substr($ico, 4, 2))[1] ?? 0;
        $max = 0;
        for ($i = 0; $i < min($count, 20); $i++) {
            $w = ord($ico[6 + $i * 16] ?? "\0");
            $max = max($max, $w === 0 ? 256 : $w);
        }
        return $max;
    }

    private function absolute(string $href, string $base): string
    {
        if (str_starts_with($href, '//')) return 'https:' . $href;
        if (preg_match('#^https?://#i', $href)) return $href;
        $p = parse_url($base);
        $root = ($p['scheme'] ?? 'https') . '://' . ($p['host'] ?? '');
        return $root . (str_starts_with($href, '/') ? $href : '/' . ltrim($href, './'));
    }
}
