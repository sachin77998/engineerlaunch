<?php

namespace App\Services\Approvals\Drivers;

use Illuminate\Support\Facades\Http;

/**
 * Shared plumbing for state portal readers. A reader returns services (each may carry inline 'documents' and/or
 * 'checklist_text'), optional per-service portal documents, and the state's policies / acts.
 */
abstract class PortalDriver
{
    public const UA = 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/124 Safari/537.36';

    public function __construct(protected object $source) {}

    /** @return array<int, array> */
    abstract public function services(): array;

    public function documents(array $service): array
    {
        return $service['documents'] ?? [];
    }

    /** @return array<int, array{kind: string, title: string, department: ?string, url: string, language: ?string}> */
    public function policies(): array
    {
        return [];
    }

    /** Address the last get() ended on after redirects; relative links resolve against it. */
    protected ?string $landed = null;

    public function get(string $url): string
    {
        $this->landed = $url;
        return Http::withHeaders(['User-Agent' => self::UA])->withOptions(['on_stats' => function ($stats) { $this->landed = (string) $stats->getEffectiveUri(); }])
            ->timeout(90)->retry(2, 1500)->get($url)->throw()->body();
    }

    public function pdf(string $url): ?string
    {
        $res = Http::withHeaders(['User-Agent' => self::UA])->withOptions(['allow_redirects' => false])->timeout(60)->retry(2, 1500, throw: false)->get($url);
        return $res->successful() && str_starts_with($res->body(), '%PDF') ? $res->body() : null;
    }

    protected function base(): string
    {
        $p = parse_url($this->source->portal_url);
        return $p['scheme'] . '://' . $p['host'];
    }

    protected function abs(?string $href, ?string $page = null): ?string
    {
        if (!$href || stripos($href, 'javascript') === 0 || str_starts_with($href, '#') || str_starts_with($href, 'mailto:')) return null;
        $href = str_replace(' ', '%20', html_entity_decode(trim($href)));
        if (preg_match('~^https?://~i', $href)) return $href;
        $root = $this->base(); $path = '/';
        if ($page && ($p = parse_url($page)) && isset($p['host'])) {
            $root = $p['scheme'] . '://' . $p['host'] . (isset($p['port']) ? ':' . $p['port'] : '');
            $path = preg_replace('~[^/]*$~', '', $p['path'] ?? '/') ?: '/';
        }
        if (str_starts_with($href, '/')) return $root . $href;
        // "../" climbs one directory but never above the site root.
        while (str_starts_with($href, '../')) { $href = substr($href, 3); $path = preg_replace('~[^/]+/$~', '', $path) ?: '/'; }
        return $root . $path . preg_replace('~^\./~', '', $href);
    }

    protected static function clean(string $html): string
    {
        $html = preg_replace('~<(br|/p|/li|/div|/tr|/h\d)\b[^>]*>~i', ' ', $html);
        return trim(preg_replace('~\s+~u', ' ', html_entity_decode(strip_tags($html), ENT_QUOTES | ENT_HTML5, 'UTF-8')));
    }

    /** HTML block to plain lines (one per paragraph / list item / line break) for the checklist parser. */
    protected static function lines(string $html): string
    {
        $html = preg_replace('~<(br|/p|/li|/div|/tr|/h\d)\b[^>]*>~i', "\n", $html);
        $text = html_entity_decode(strip_tags($html), ENT_QUOTES | ENT_HTML5, 'UTF-8');
        return trim(implode("\n", array_filter(array_map(fn ($l) => trim(preg_replace('~\h+~u', ' ', $l)), preg_split('~\R~u', $text)), fn ($l) => $l !== '')));
    }

    protected function dom(string $html): \DOMXPath
    {
        $doc = new \DOMDocument();
        libxml_use_internal_errors(true);
        $doc->loadHTML('<?xml encoding="UTF-8">' . $html, LIBXML_NOWARNING | LIBXML_NOERROR | LIBXML_COMPACT);
        libxml_clear_errors();
        return new \DOMXPath($doc);
    }

    protected static function inner(\DOMNode $node): string
    {
        $html = '';
        foreach ($node->childNodes as $c) $html .= $node->ownerDocument->saveHTML($c);
        return $html;
    }

    /** Policy PDFs linked from a page: [title => url] using the link text, else the file name. */
    protected function policyLinks(string $html, string $page, string $pattern = '~polic|act|rules|scheme|guideline~i'): array
    {
        $out = [];
        preg_match_all('~<a\b[^>]*href="([^"]+\.pdf)"[^>]*>(.*?)</a>~is', $html, $m, PREG_SET_ORDER);
        foreach ($m as [, $href, $text]) {
            $url = $this->abs($href, $page);
            $title = self::clean($text);
            if ($title === '' || strlen($title) < 6 || preg_match('~^(view|download|click|pdf|english|hindi)\b~i', $title)) {
                $title = ucwords(trim(preg_replace(['~\.pdf$~i', '~[_-]+~', '~%20~'], ['', ' ', ' '], basename(parse_url($url, PHP_URL_PATH)))));
            }
            if ($url && preg_match($pattern, $title . ' ' . $url)) $out[$url] = $title;
        }
        return $out;
    }
}
