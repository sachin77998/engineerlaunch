<?php

namespace App\Services\Insights;

// Reads an equity funding round from a news headline (and its first lines):
//   "Bengaluru-based fintech Jar raises $50 mn in Series D led by Tiger Global, with participation from Arkam Ventures - Inc42"
//   "Peak XV leads ₹120 crore Series A in healthtech startup Even Healthcare"
// Returns null when the text is not about a company raising a round.
class FundingRoundExtractor
{
    private const AMOUNT = '(US\$|USD|\$|Rs\.?|INR|₹|€|EUR|£|GBP|S\$|SGD|AED)\s?([\d,]+(?:\.\d+)?)\s?(million|mn|mln|m|billion|bn|b|crore|cr|lakh|lakhs|k)?\b';
    private const RAISE = '(?:raises?|raised|secures?|secured|bags?|bagged|nets?|netted|gets?|got|lands?|landed|closes?|closed|picks\s+up|picked\s+up|scoops?\s+up|attracts?|receives?|received|mops\s+up|announces?)';
    // Rough rates to USD for sorting and totals only; the original amount and currency are what the page shows.
    private const TO_USD = ['USD' => 1, 'INR' => 0.0119, 'EUR' => 1.08, 'GBP' => 1.27, 'SGD' => 0.74, 'AED' => 0.272];
    private const NOT_INVESTOR = '/^(?:others?|existing\s+(?:investors?|backers?|shareholders?)|(?:a\s+clutch\s+of\s+|several\s+|other\s+|marquee\s+|prominent\s+)?(?:angel\s+investors?|angels|investors|hnis?|family\s+offices?|high[- ]net[- ]worth\s+individuals?)|founders?|its\s+founders?|employees|strategic\s+investors?|new\s+investors?|global\s+investors?|undisclosed\s+investors?|clients?|the\s+company)$/i';
    private const NOT_COMPANY = '/^(?:startup|startups|company|firm|it|india|indian|the|this|platform|government|centre|state|ai|fintech|edtech|healthtech|saas|d2c)$|^(?:FY\s?)?\d{2,4}$|^Q[1-4]$|^(?:this|these|why|how|top|best|new)\b|\b(?:stocks?|shares|facility|facilities|plant|plants|factory|factories|unit|units|capacity|sector|industry|market|markets|ecosystem|campus|computing|project|projects|economy)$/iu';
    // "raises guidance / prices / stake / concerns": the verb is not about funding.
    private const NOT_FUNDING_RAISE = '/^(?:its\s+|the\s+)?(?:FY\s?\d{2,4}\s+)?(?:\w+\s+)?(?:guidance|target|targets|prices?|rates?|stake|limits?|outlook|forecast|concerns?|questions?|bar|tariffs?|capex|dividend|alarm|eyebrows|red\s+flags?|voice|objections?|demand)\b/iu';

    public function extract(string $title, string $body = ''): ?array
    {
        $title = $this->clean($title);
        if (!preg_match('/\b(?:funding|round|series\s+[a-h]\d?|seed|pre-seed|raise[sd]?|investment|invests?|led\s+by|backed)\b/i', $title)) return null;
        // Capex plans, listicles, funds raising their own corpus and market round-ups are not a company's round.
        if (preg_match('/\bto\s+invest\b|\b\d+\s+(?:best|top)\b|\bfund(?:ing)?\s+pool\b|\bfor\s+(?:its\s+)?(?:\w+\s+)?fund\b|\bfunding\s+(?:surge|slump|winter|drops?|falls?|rises?)\b/iu', $title)) return null;
        if (preg_match('/\b(?:fund\s+(?:size|corpus)|launches?\s+(?:a\s+)?(?:\$|rs|₹)?[\d,.]*\s*\w*\s*fund|closes?\s+(?:its\s+)?(?:first|second|third|final|maiden)?\s*(?:fund|close)|ipo\s+(?:opens|subscribed|price|gmp|allotment)|mutual\s+fund|sip|nfo|\bQIP\b|rights\s+issue|bond|ncd|debenture|loan|layoffs?|valuation\s+cut)\b/i', $title)) return null;

        $round = $this->roundType($title . ' ' . mb_substr($body, 0, 400));
        $amount = $this->amount($title) ?? ($round ? $this->amount(mb_substr($body, 0, 300)) : null);
        $company = null;
        $leads = $others = [];

        // "{Investor} leads {amount} {round} in {Company}" / "{Investor} invests {amount} in {Company}"
        if (preg_match('/^(.+?)\s+(?:leads?|led|co-leads?|invests?|invested|backs?|backed|puts?|bets?)\s+(?:.{0,80}?\s)?(?:in|into|on)\s+(.+?)(?:\s*(?:[,;:|]|\s-\s|\s+(?:to|for|as|amid|at|with|after|ahead)\s).*)?$/iu', $title, $m)
            && !preg_match('/' . self::RAISE . '/iu', $m[1])) {
            $company = $this->companyName($m[2]);
            $leads = $this->investors($m[1]);
        }
        // "{Company} raises {amount} {round} (led by|from) {investors}"
        if (!$company && preg_match('/^(.+?)\s+' . self::RAISE . '\s+(.*)$/iu', $title, $m)) {
            $company = preg_match(self::NOT_FUNDING_RAISE, $m[2]) ? null : $this->companyName($m[1]);
            $rest = $m[2];
            if (preg_match('/\b(?:co-)?led\s+by\s+(.+?)(?=,?\s+(?:with|and\s+others|alongside|along\s+with)\b|[;|]|\s-\s|\s+(?:to|for|at)\s|$)/iu', $rest, $l)) $leads = $this->investors($l[1]);
            if (preg_match('/\b(?:participation\s+(?:from|of)|alongside|along\s+with|others\s+including|backed\s+by|participated\s+by|investors\s+including|\bfrom)\s+(.+?)(?=[;|]|\s-\s|\s+(?:to|for|at)\s|$)/iu', $rest, $o)) $others = $this->investors($o[1]);
        }
        if (!$company || (!$round && !$amount && !$leads)) return null;

        // The opening lines often name investors the headline left out.
        if ($body !== '') {
            $lead = mb_substr($this->clean($body), 0, 600);
            if (!$leads && preg_match('/\b(?:co-)?led\s+by\s+(.+?)(?=,?\s+(?:with|alongside|along\s+with)\b|[.;]|$)/iu', $lead, $l)) $leads = $this->investors($l[1]);
            if (!$others && preg_match('/\b(?:participation\s+(?:from|of)|alongside|along\s+with|also\s+saw\s+participation\s+from)\s+(.+?)(?=[.;]|$)/iu', $lead, $o)) $others = $this->investors($o[1]);
        }
        $others = array_values(array_filter($others, fn ($n) => !in_array($n, $leads, true) && $n !== $company));
        $leads = array_values(array_filter($leads, fn ($n) => $n !== $company));

        return [
            'company' => $company,
            'round_type' => $round ?? ($amount || $leads ? 'undisclosed' : null),
            'amount' => $amount['amount'] ?? null,
            'currency' => $amount['currency'] ?? null,
            'amount_usd' => $amount ? (round($amount['amount'] * (self::TO_USD[$amount['currency']] ?? 0), 2) ?: null) : null,
            'leads' => $leads,
            'investors' => $others,
            'headline' => $title,
        ];
    }

    /** Best guess at what kind of investor a name is. */
    public function investorType(string $name): string
    {
        return match (true) {
            (bool) preg_match('/\b(?:angels?|angel\s+network|angel\s+fund|lets\s*venture|venture\s+catalysts)\b/i', $name) => 'angel_network',
            (bool) preg_match('/\b(?:accelerator|incubat\w+|y\s*combinator|techstars|antler|500\s+(?:global|startups)|sprint)\b/i', $name) => 'accelerator',
            (bool) preg_match('/\b(?:sidbi|nabard|government|ministry|state\s+of|startup\s+india|fund\s+of\s+funds|kerala\s+startup|temasek|gic|mubadala|adia|qia|pif|sovereign)\b/i', $name) => 'government',
            (bool) preg_match('/\b(?:capital|ventures?|vc|partners|fund|funds|investments?|advisors|equity|growth|seed|labs|holdings?\s+fund|global|pe)\b/i', $name) => 'vc',
            (bool) preg_match('/\b(?:bank|securities|asset\s+management|amc|finance|financial|insurance|life|nbfc|motilal|kotak|hdfc|icici|axis|sbi|jm\s+financial)\b/i', $name) => 'financial',
            (bool) preg_match('/\b(?:limited|ltd|inc|corp|corporation|group|industries|technologies|motors|enterprises|plc|gmbh|ag)\b\.?$/i', $name) => 'corporate',
            (bool) preg_match('/^(?:[A-Z][a-z]+\.?\s){1,2}[A-Z][a-z]+$/u', $name) => 'individual',
            default => 'firm',
        };
    }

    private function clean(string $s): string
    {
        $s = html_entity_decode(strip_tags($s), ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $s = preg_replace('/\s+/u', ' ', trim($s));
        $s = preg_replace('/\s+[-–—|]\s+[^-–—|]{2,60}$/u', '', $s);            // " - Inc42", " | Mint"
        $s = preg_replace('/^(?:exclusive|breaking|funding\s+(?:alert|news|roundup)|update|report|scoop|deal\s*watch)\s*[:|-]\s*/iu', '', $s);
        return trim($s, " \t\"'‘’“”");
    }

    private function roundType(string $s): ?string
    {
        $s = mb_strtolower($s);
        if (preg_match('/\bpre[- ]series[- ]([a-h])\b/', $s, $m)) return $m[1] === 'a' ? 'pre_series_a' : 'series_' . chr(ord($m[1]) - 1);
        if (preg_match('/\bseries[- ]([a-h])\d?\+?\b/', $s, $m)) return 'series_' . $m[1];
        return match (true) {
            (bool) preg_match('/\bpre[- ]seed\b/', $s) => 'pre_seed',
            (bool) preg_match('/\bpre[- ]ipo\b/', $s) => 'pre_ipo',
            (bool) preg_match('/\bseed\b|\bangel\s+round\b/', $s) => 'seed',
            (bool) preg_match('/\bbridge\b/', $s) => 'bridge',
            (bool) preg_match('/\bventure\s+debt\b/', $s) => 'debt',
            (bool) preg_match('/\bgrowth\s+(?:round|funding|capital)\b/', $s) => 'growth',
            (bool) preg_match('/\bstrategic\s+(?:investment|round|funding)\b/', $s) => 'strategic',
            default => null,
        };
    }

    private function amount(string $s): ?array
    {
        if (!preg_match('/' . self::AMOUNT . '/iu', $s, $m)) return null;
        $currency = ['US$' => 'USD', '$' => 'USD', 'USD' => 'USD', 'RS' => 'INR', 'RS.' => 'INR', 'INR' => 'INR', '₹' => 'INR', '€' => 'EUR', 'EUR' => 'EUR', '£' => 'GBP', 'GBP' => 'GBP', 'S$' => 'SGD', 'SGD' => 'SGD', 'AED' => 'AED'][mb_strtoupper($m[1])] ?? null;
        $v = (float) str_replace(',', '', $m[2]);
        $unit = mb_strtolower($m[3] ?? '');
        $v *= match ($unit) { 'million', 'mn', 'mln', 'm' => 1e6, 'billion', 'bn', 'b' => 1e9, 'crore', 'cr' => 1e7, 'lakh', 'lakhs' => 1e5, 'k' => 1e3, default => 1 };
        // A bare "$5" or "Rs 40" is not a funding amount.
        if (!$currency || $v < 10000) return null;
        return ['amount' => round($v, 2), 'currency' => $currency];
    }

    // "Bengaluru-based fintech startup Jar" → "Jar"; "Ather Energy" → "Ather Energy"; "upGrad" → "upGrad".
    private function companyName(string $s): ?string
    {
        $s = preg_replace('/^(?:.*?:\s+)/u', '', trim($s));
        $s = preg_replace('/\s*\(.*?\)\s*/u', ' ', $s);
        $s = preg_replace("/['’]s\b.*$/u", '', $s);
        $s = preg_replace('/\b(?:startup|start-up|firm|company|platform|brand|maker|provider|player|unicorn|major)\s*$/iu', '', trim($s));
        $tokens = preg_split('/\s+/u', trim($s, " ,.-")) ?: [];
        $name = [];
        for ($i = count($tokens) - 1; $i >= 0; $i--) {
            $t = $tokens[$i];
            $brand = preg_match('/^[\p{Lu}\d]/u', $t) || preg_match('/^\p{Ll}+\p{Lu}/u', $t) || (in_array(mb_strtolower($t), ['&', 'of', 'and', 'de', 'la'], true) && $name);
            if (!$brand || preg_match('/-(?:based|backed|headquartered|founded|led|incubated|bound|listed|focused|owned)$/iu', $t)) break;
            array_unshift($name, $t);
        }
        while ($name && in_array(mb_strtolower($name[0]), ['&', 'of', 'and', 'de', 'la'], true)) array_shift($name);
        $name = trim(implode(' ', $name), " ,.-");
        if ($name === '' || mb_strlen($name) > 80 || count($name ? explode(' ', $name) : []) > 6 || preg_match(self::NOT_COMPANY, $name)) return null;
        return $name;
    }

    /** "Accel, Elevation and Lightspeed among others" → ["Accel", "Elevation", "Lightspeed"]. */
    private function investors(string $s): array
    {
        $s = preg_replace('/\b(?:among\s+others|and\s+others|amongst\s+others|others|in\s+(?:a|its|the)\s+.*$)\b/iu', '', $s);
        $s = preg_replace('/^(?:existing\s+investors?|investors?|its\s+existing\s+investors?)\s+(?:such\s+as|like|including)\s+/iu', '', trim($s));
        $out = [];
        foreach (preg_split('/\s*(?:,|;|\s&\s|\band\b|\bas\s+well\s+as\b|\bwith\b)\s*/iu', $s) ?: [] as $part) {
            $part = trim(preg_replace('/^(?:existing\s+investors?|investors?|new\s+investor|also|its|the|a)\s+/iu', '', trim($part)), " .'\"‘’“”");
            $part = preg_replace('/\s+(?:also|too|as\s+well)$/iu', '', $part);
            if ($part === '' || mb_strlen($part) < 2 || mb_strlen($part) > 80 || preg_match(self::NOT_INVESTOR, $part) || !preg_match('/^[\p{Lu}\d]|^\p{Ll}+\p{Lu}/u', $part)) continue;
            if (count(explode(' ', $part)) > 7) continue;
            $out[] = $part;
        }
        return array_values(array_unique($out));
    }
}
