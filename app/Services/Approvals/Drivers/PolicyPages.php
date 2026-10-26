<?php

namespace App\Services\Approvals\Drivers;

/**
 * Policies only: links titled as policies / acts / rules on the policies page, plus policy PDFs referenced from the
 * page's script bundles (single-page apps). For portals whose services are not published openly.
 */
class PolicyPages extends PortalDriver
{
    public function services(): array
    {
        return [];
    }

    public function policies(): array
    {
        $page = $this->source->policies_url ?: $this->source->portal_url;
        $html = $this->get($page);
        $page = $this->landed ?: $page;
        $found = [];
        preg_match_all('~<a\b[^>]*href=["\']([^"\']+)["\'][^>]*>(.*?)</a>~is', $html, $m, PREG_SET_ORDER);
        foreach ($m as [, $href, $text]) {
            $title = self::clean($text);
            $url = $this->abs($href, $page);
            // Menu headings ("Policy Framework", "Other Policies", "Acts/ Rules/ ...") lead to listing pages, not a policy.
            $menu = preg_match('~^(the\s+)?(policy|policies|policy framework|sectoral policies|other policies|policy dashboard|industrial policies|state policies)$|^acts?\s*/~i', $title);
            if ($url && !$menu && strlen($title) >= 6 && preg_match('~\b(polic(y|ies)|act|rules|scheme)\b~i', $title) && !preg_match('~privacy|cookie|refund|terms|hyperlink|copyright|accessib~i', $title)) $found[$url] = $title;
        }
        preg_match_all('~<script\b[^>]*src=["\']([^"\']+\.js)["\']~i', $html, $scripts);
        foreach (array_slice($scripts[1], 0, 6) as $src) {
            $js = $this->abs($src, $page);
            if (!$js || parse_url($js, PHP_URL_HOST) !== parse_url($page, PHP_URL_HOST)) continue;
            try { $body = $this->get($js); } catch (\Throwable) { continue; }
            preg_match_all('~https?://[A-Za-z0-9.-]+/[A-Za-z0-9_./%-]*polic[A-Za-z0-9_./%-]*\.pdf~i', $body, $pdfs);
            foreach ($pdfs[0] as $url) $found[$url] ??= ucwords(trim(preg_replace(['~\.pdf$~i', '~[_-]+~', '~%20~', '~\s+~'], ['', ' ', ' ', ' '], basename(parse_url($url, PHP_URL_PATH)))));
        }
        $out = [];
        foreach ($found as $url => $title) {
            $kind = preg_match('~\bpolic(y|ies)\b~i', $title) ? 'policy' : (preg_match('~\brules?\b~i', $title) ? 'rule' : 'act');
            $out[] = ['kind' => $kind, 'title' => $title, 'department' => null, 'url' => $url, 'language' => null];
        }
        return $out;
    }
}
