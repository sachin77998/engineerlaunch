<?php
namespace App\Services\Insights;

use Carbon\Carbon;
use Throwable;

/**
 * Reads an ICRA or CARE rating rationale (text from PdfText) into structured data: entity, date, rating action,
 * long/short-term ratings and outlook, rated facilities, debt instrument details (ISIN, coupon, maturity),
 * agency-reported financials, liquidity, credit strengths/challenges and rating sensitivities.
 */
class RatingRationaleParser
{
    // Long-term scale best to worst; short-term scale likewise. Score = position from the bottom (higher is better).
    public const LONG_SCALE = ['AAA', 'AA+', 'AA', 'AA-', 'A+', 'A', 'A-', 'BBB+', 'BBB', 'BBB-', 'BB+', 'BB', 'BB-', 'B+', 'B', 'B-', 'C+', 'C', 'C-', 'D'];
    public const SHORT_SCALE = ['A1+', 'A1', 'A2+', 'A2', 'A3+', 'A3', 'A4+', 'A4', 'D'];
    private const MONTHS = 'January|February|March|April|May|June|July|August|September|October|November|December|Jan|Feb|Mar|Apr|Jun|Jul|Aug|Sep|Sept|Oct|Nov|Dec';
    private const LONG = '(?:\[ICRA\]|CARE\s|IND\s|CRISIL\s|Crisil\s|ACUITE\s|BWR\s)(AAA|AA[+-]?|A[+-]?|BBB[+-]?|BB[+-]?|B[+-]?|C[+-]?|D)(?![0-9A-Za-z+])(?:\s*\((?:SO|CE)\))?';
    private const SHORT = '(?:\[ICRA\]|CARE\s|IND\s|CRISIL\s|Crisil\s|ACUITE\s|BWR\s)(A1\+?|A2\+?|A3\+?|A4\+?)(?:\s*\((?:SO|CE)\))?';
    private const OUTLOOK = '(Stable|Positive|Negative|Developing)';

    public static function score(?string $rating): ?int
    {
        if (!$rating || !preg_match('/(AAA|AA[+-]?|A[+-]?|BBB[+-]?|BB[+-]?|B[+-]?|C[+-]?|D)(?![0-9])/', $rating, $m)) return null;
        $i = array_search($m[1], self::LONG_SCALE, true);
        return $i === false ? null : count(self::LONG_SCALE) - $i;
    }

    public function parse(string $text): array
    {
        $out = $this->read($text);
        // Strings cut from PDF text must stay valid UTF-8 for JSON columns.
        array_walk_recursive($out, function (&$v) { if (is_string($v)) $v = mb_scrub($v, 'UTF-8'); });
        return $out;
    }

    private function read(string $text): array
    {
        $text = str_replace(["\r", "\u{00A0}"], ['', ' '], $text);
        $pages = explode("\f", $text);
        $lines = array_values(array_filter(array_map('trim', explode("\n", str_replace("\f", "\n", $text))), fn ($l) => $l !== ''));
        $flat = preg_replace('/\s+/', ' ', implode(' ', $lines));
        $agency = $this->agency($flat);
        [$entity, $title] = $this->entity($lines, $agency);
        $summary = $this->summaryRows($lines, $agency);
        $rows = array_values(array_filter($summary, fn ($r) => !$r['total']));
        $totalRow = collect($summary)->firstWhere('total', true);
        $head = implode(' ', array_map(fn ($r) => $r['rating_text'], $rows)) . ' ' . ($title ?? '');
        $long = $this->firstLong($head); $short = $this->firstShort($head);
        $withdrawnAll = $rows && (collect($rows)->every(fn ($r) => $r['withdrawn']) || (collect($rows)->contains('withdrawn', true) && collect($rows)->every(fn ($r) => !($r['current'] ?? 0))));
        $action = $this->action(($title ?? '') . ' ' . implode(' ', array_map(fn ($r) => $r['action'] ?? '', $rows)), $withdrawnAll);
        $total = $totalRow['current'] ?? (collect($rows)->sum(fn ($r) => $r['current'] ?? 0) ?: null);
        return [
            'agency' => $agency,
            'entity_name' => $entity,
            'title' => $title,
            'published_on' => $this->date($pages[0] ?? $text),
            'action' => $action,
            'rating_long' => $long['rating'] ?? null,
            'rating_short' => $short,
            'outlook' => $long['outlook'] ?? $this->outlook($head),
            'previous_rating_long' => $this->previousLong($head),
            'score' => self::score($long['rating'] ?? null),
            'total_rated_crore' => $total,
            'withdrawn' => $withdrawnAll || $action === 'withdrawn',
            'non_cooperating' => (bool) preg_match('/issuer\s+not\s+cooperating|non-cooperating/i', preg_replace('/(removed\s+)?from\s+(the\s+)?issuer\s+non.?\s*cooperating(\s+category)?/i', ' ', ($title ?? '') . ' ' . $head)),
            'liquidity' => preg_match('/Liquidity(?:\s+position)?\s*[:–-]\s*(Superior|Strong|Adequate|Stretched|Poor)/iu', $flat, $m) ? ucfirst(strtolower($m[1])) : null,
            'instruments' => array_map(fn ($r) => array_diff_key($r, ['rating_text' => 1, 'total' => 1]), $rows),
            'debt_instruments' => $this->annexure($lines),
            'financials' => $this->financials($lines),
            'strengths' => $this->drivers($flat, '(?:Key rating strengths|Credit strengths)', '(?:Key rating weaknesses|Credit challenges|Key rating weakness)')
                ?: $this->lineDrivers($lines, '/^(Key strengths|Key rating strengths|Credit strengths)$/i', '/^(Key weaknesses|Key rating weaknesses|Credit challenges)$/i'),
            'challenges' => $this->drivers($flat, '(?:Key rating weaknesses|Key rating weakness|Credit challenges)', '(?:Liquidity|Environmental and social|Analytical approach|Rating sensitivities|ESG)')
                ?: $this->lineDrivers($lines, '/^(Key weaknesses|Key rating weaknesses|Credit challenges)$/i', '/^(Liquidity|Environment|Applicable criteria|Analytical approach|Rating sensitivities|Assumptions|ESG)/i'),
            'sensitivities' => $this->sensitivities($flat),
            'about' => $this->about($flat),
            // Rationale and liquidity text, and the funding events they describe (infusions, repayments, tie-ups, capex).
            'rationale' => ($x = app(FundingEventExtractor::class))->rationale($text),
            'liquidity_text' => $x->liquidity($text),
            'funding_events' => $x->extract($text),
        ];
    }

    private function agency(string $flat): ?string
    {
        if (preg_match('/\[ICRA\]|ICRA Limited|www\.icra\s?\.in/i', $flat)) return 'ICRA';
        if (preg_match('/CARE Ratings|careedge|CARE (AAA|AA|A|BBB|BB|B|C|D|A1|A2|A3|A4)/i', $flat)) return 'CARE';
        return null;
    }

    /** Entity and headline. ICRA: "Entity: action" after the date; CARE: entity line before the date. */
    private function entity(array $lines, ?string $agency): array
    {
        $dateRe = '/^(' . self::MONTHS . ')\s+\d{1,2}\s*,\s*\d{4}$/i';
        foreach (array_slice($lines, 0, 12) as $i => $line) {
            if (!preg_match($dateRe, $line)) continue;
            if ($agency === 'CARE') {
                $name = $lines[$i - 1] ?? null;
                if ($name && preg_match('/^\(Revised\)$/i', $name)) $name = $lines[$i - 2] ?? $name;
                $name = trim(preg_replace('/\(Revised\)/i', '', (string) $name));
                return [$name ?: null, $name ?: null];
            }
            $title = $lines[$i + 1] ?? '';
            // The headline may wrap; continue while the next line does not start a section.
            for ($j = $i + 2; $j < $i + 5 && isset($lines[$j]) && !preg_match('/^(Summary of rating action|Rationale|Ratings?$|Instrument)/i', $lines[$j]); $j++) $title .= ' ' . $lines[$j];
            $title = trim(preg_replace('/\s+/', ' ', preg_replace('/\s*-\s+(?=[a-z])/', '-', $title)));
            $name = trim(preg_split('/\s*:\s/', $title, 2)[0] ?? '');
            return [$name !== '' ? mb_substr($name, 0, 255) : null, mb_substr($title, 0, 500)];
        }
        return [null, null];
    }

    private function date(string $text): ?string
    {
        if (!preg_match('/\b(' . self::MONTHS . ')\s+(\d{1,2})\s*,\s*(20\d{2})\b/i', $text, $m)) return null;
        try { return Carbon::parse("$m[1] $m[2] $m[3]")->toDateString(); } catch (Throwable) { return null; }
    }

    private function action(string $text, bool $withdrawnAll): string
    {
        $t = strtolower($text);
        if ($withdrawnAll) return 'withdrawn';
        $t = preg_replace('/(removed\s+)?from\s+(the\s+)?issuer\s+non.?\s*cooperating(\s+category)?/', ' ', $t);
        foreach (['downgrad' => 'downgraded', 'upgrad' => 'upgraded', 'revised from' => 'revised', 'non-cooperating' => 'non-cooperating', 'not cooperating' => 'non-cooperating',
                  'watch' => 'watch', 'reaffirm' => 'reaffirmed', 'assigned' => 'assigned', 'confirmed as final' => 'confirmed', 'withdrawn' => 'withdrawn', 'revised' => 'revised'] as $needle => $label) {
            if (str_contains($t, $needle)) return $label;
        }
        return 'other';
    }

    private function firstLong(string $text): ?array
    {
        // Ignore the previous rating in "upgraded from [ICRA]A- (Stable)" / "Revised from CARE BB; Stable".
        $current = preg_replace('/(upgraded|downgraded|revised|moved)\s+from\s+[^;,)]*\)?/i', ' ', $text);
        if (!preg_match('/' . self::LONG . '(?:\s*[;(]\s*(?:Outlook:\s*)?' . self::OUTLOOK . ')?/', $current, $m)) return null;
        return ['rating' => $m[1] . (preg_match('/\((SO|CE)\)/', $m[0], $x) ? "($x[1])" : ''), 'outlook' => $m[2] ?? null];
    }

    private function firstShort(string $text): ?string
    {
        $current = preg_replace('/(upgraded|downgraded|revised|moved)\s+from\s+[^;,)]*\)?/i', ' ', $text);
        return preg_match('/' . self::SHORT . '/', $current, $m) ? $m[1] : null;
    }

    private function previousLong(string $text): ?string
    {
        return preg_match('/(?:upgraded|downgraded|revised)\s+from\s+' . self::LONG . '/i', $text, $m) ? $m[1] : null;
    }

    private function outlook(string $text): ?string
    {
        return preg_match('/\b' . self::OUTLOOK . '\b/', $text, $m) ? $m[1] : null;
    }

    /** Rated facilities from the summary table; wrapped cells are re-joined around the line holding the amounts. */
    private function summaryRows(array $lines, ?string $agency): array
    {
        $start = null;
        foreach (array_slice($lines, 0, 60) as $i => $l) {
            if (preg_match('/^(Summary of rating action|Ratings$|Facilities\s*\/\s*Instruments)/i', $l)) { $start = $i; break; }
        }
        if ($start === null) return [];
        $rows = []; $pending = []; $current = null;
        $amountLine = function (string $l) {
            $l = preg_replace('/\([^)]*\)/', ' ', $l);
            return (bool) preg_match('/(^|\s)(\d[\d,]*\.\d{1,2}|\d{1,3}(,\d{2,3})+|-)(\s|$)/', $l) && !preg_match('/^(Previous|Current|Initial|Instrument|Trust|Amount|\(Rs)/i', $l);
        };
        $hasRating = fn ($l) => (bool) preg_match('/' . self::LONG . '|' . self::SHORT . '|Withdrawn|Provisional/i', $l);
        for ($i = $start + 1; $i < min(count($lines), $start + 70); $i++) {
            $l = $lines[$i];
            if (preg_match('/^(\*|#|Details of instruments|Rationale$|Rationale and|Detailed rationale|Rating in the absence|Note:)/i', $l)) break;
            if (preg_match('/^(Previous|Current|Initial|Instrument\*?|Trust name|amount|\(Rs\.? crore\)|rated|Rating action|Financial|sector|regulator|Regulator|Amount|Facilities\s*\/\s*Instruments)/i', $l) && !$amountLine($l)) continue;
            if ($amountLine($l)) {
                if ($current) $rows[] = $current;
                $current = ['parts' => array_merge($pending, [$l]), 'anchor' => $l, 'starts_with_amount' => (bool) preg_match('/^(\d|-\s)/', $l)];
                $pending = [];
                continue;
            }
            // Text after an anchor that began with an amount belongs to that row, unless it starts a new rating.
            if ($current && $current['starts_with_amount'] && !($hasRating($l) && $hasRating(implode(' ', $current['parts'])))) { $current['parts'][] = $l; continue; }
            $pending[] = $l;
        }
        if ($current) $rows[] = $current;
        return array_values(array_filter(array_map(fn ($r) => $this->row($r), $rows)));
    }

    private function row(array $r): ?array
    {
        $text = preg_replace('/\s+/', ' ', implode(' ', $r['parts']));
        $anchor = preg_replace('/\([^)]*\)/', ' ', $r['anchor']);
        preg_match_all('/(?<=^|\s)(\d[\d,]*(?:\.\d+)?|-)(?=\s|$)/', $anchor, $m);
        $amounts = array_map(fn ($v) => $v === '-' ? null : (float) str_replace(',', '', $v), $m[1]);
        $total = (bool) preg_match('/^[\d.,\s-]*Total\b/i', trim($text)) || (bool) preg_match('/\bTotal\b/', $r['anchor']) || (bool) preg_match('/^\S+\s+Total\b/', trim($text));
        // Instrument = the row text without amounts, ratings, actions, regulators and the agencies' rating words.
        $name = preg_replace([
            '/\((?:[^()]*(Double|Single|Triple|A One|A Two|A Three|A Four|Outlook|Enhanced|Reduced|Rs\.|₹)[^()]*)\)?/i', '/' . self::LONG . '(\s*\(' . self::OUTLOOK . '\))?/', '/' . self::SHORT . '/',
            '/\b(Provisional|Stable|Positive|Negative|Developing|Outlook)\b:?/i', '/\b(reaffirmed|assigned|upgraded|downgraded|withdrawn|withdraw|revised|from|and|for|enhanced|amount|rated|rating|confirmed as final|placed on|watch with|implications|removed|issuer not cooperating|non-cooperating|category|moved to|simultaneously|matured|NCDs?)\b(?=\s|;|,|$)/i',
            '/(?<=^|\s)(\d[\d,]*(?:\.\d+)?|-)(?=\s|$)/', '/\b(RBI|SEBI|MCA|IRDAI|PFRDA)\$?\b/', '/[;\/]\s*(?=;|\s|$)/', '/ISSUER NOT COOPERATING\*?|ISSUER NON|COOPERATING|[“”"$*@^#]|\bNA\b/iu', '/\s+/',
        ], ['', '', '', '', '', '', '', '', ' ', ' '], $text);
        $name = trim(preg_replace('/^[\s;,:\/–-]+|[\s;,:\/–-]+$/u', '', $name));
        $name = preg_replace('/(\w)-\s+(\w)/', '$1-$2', $name);
        if ($total) return ['instrument' => 'Total', 'current' => $amounts ? end($amounts) : null, 'previous' => count($amounts) > 1 ? $amounts[count($amounts) - 2] : null, 'total' => true, 'rating_text' => '', 'withdrawn' => false];
        if (!$amounts || ($name === '' && !array_filter($amounts, fn ($a) => $a !== null))) return null;
        $long = $this->firstLong($text); $short = $this->firstShort($text);
        if (!$long && !$short && !array_filter($amounts, fn ($x) => $x !== null)) return null;
        $withdrawn = (bool) preg_match('/withdrawn/i', $text);
        return [
            'instrument' => mb_substr($name !== '' ? $name : 'Rated facility', 0, 160),
            'type' => $this->type($name),
            'previous' => count($amounts) > 1 ? $amounts[count($amounts) - 2] : null,
            'current' => $withdrawn ? 0.0 : ($amounts ? end($amounts) : null),
            'rating' => $long ? $long['rating'] : null,
            'rating_short' => $short,
            'outlook' => $long['outlook'] ?? null,
            'action' => $this->action($text, false),
            'withdrawn' => $withdrawn,
            'regulator' => preg_match('/\b(RBI|SEBI|MCA|IRDAI)\b/', $text, $x) ? $x[1] : null,
            'rating_text' => $text,
            'total' => false,
        ];
    }

    public function type(string $name): string
    {
        $n = strtolower($name);
        return match (true) {
            (bool) preg_match('/ptc|pass.through|securiti[sz]|second loss|series a/', $n) => 'Securitisation (PTCs)',
            (bool) preg_match('/commercial paper|\bcp\b/', $n) => 'Commercial paper',
            (bool) preg_match('/subordinat|tier ii|perpetual/', $n) => 'Subordinated debt',
            (bool) preg_match('/debenture|ncd|bond|market linked/', $n) => 'Debentures & bonds',
            (bool) preg_match('/non.fund|bank guarantee|letter of credit|\blc\b|guarantee/', $n) => 'Non-fund based limits',
            (bool) preg_match('/term loan|external commercial|ecb/', $n) => 'Term loans',
            (bool) preg_match('/cash credit|working capital|overdraft|packing credit|bill|fund.based|bank (loan|facilit|lines)|long.term|short.term/', $n) => 'Working capital & bank lines',
            (bool) preg_match('/fixed deposit|deposit/', $n) => 'Fixed deposits',
            default => 'Other',
        };
    }

    /** Annexure instrument rows: ISIN, coupon, issue date, maturity, amount, rating. Dates wrapped onto neighbouring lines are re-joined. */
    private function annexure(array $lines): array
    {
        $start = null;
        foreach ($lines as $i => $l) if (preg_match('/Annexure[\s-]*(I|1)\s*[:–-]\s*(Instrument details|Details of instruments)/i', $l)) { $start = $i; break; }
        if ($start === null) return [];
        $out = [];
        $end = min(count($lines), $start + 120);
        for ($i = $start + 1; $i < $end; $i++) {
            $l = $lines[$i];
            if (preg_match('/^(Source:|Annexure[\s-]*(II|2)|Please click|#SEBI|\*\s)/i', $l)) break;
            if (!preg_match('/(\d+(?:\.\d+)?)\s+(?:' . self::LONG . '|' . self::SHORT . '|Provisional|Withdrawn|\[ICRA\]|CARE\s)/i', $l) && !preg_match('/\bIN[A-Z0-9]{10}\b/', $l)) continue;
            $prev = $lines[$i - 1] ?? ''; $next = $lines[$i + 1] ?? '';
            if (preg_match('/^(ISIN|Instrument|issuance|\(Rs|Amount|name|Date of)/i', $prev)) $prev = '';
            $window = "$prev $l $next";
            $isin = preg_match('/\b(IN[A-Z0-9]{10})\b/', $l, $x) ? $x[1] : null;
            $coupon = preg_match('/(\d{1,2}\.\d{1,2})\s?%/', $l, $x) ? (float) $x[1] : null;
            // Dates: full "Oct 17, 2018" / "30/07/2024" on the row, or month-day above and the year below.
            $dates = [];
            preg_match_all('/\b(' . self::MONTHS . ')\s+(\d{1,2}),?\s+(20\d{2}|19\d{2})\b/i', $l, $full, PREG_SET_ORDER);
            foreach ($full as $f) $dates[] = $this->ymd("$f[1] $f[2] $f[3]");
            preg_match_all('/\b(\d{2})[\/-](\d{2})[\/-](\d{4})\b/', $l, $num, PREG_SET_ORDER);
            foreach ($num as $f) $dates[] = $this->ymd("$f[3]-$f[2]-$f[1]");
            if (count($dates) < 2) {
                preg_match_all('/\b(' . self::MONTHS . ')\s+(\d{1,2}),?(?!\s*\d{4})/i', $prev, $md, PREG_SET_ORDER);
                preg_match_all('/\b(20\d{2}|19\d{2})\b/', $next, $yr);
                foreach ($md as $k => $f) if (isset($yr[1][$k])) $dates[] = $this->ymd("$f[1] $f[2] {$yr[1][$k]}");
            }
            $dates = array_values(array_filter($dates));
            if (!$dates && preg_match_all('/\bFY\s?(20\d{2})\b/', $l, $fy)) $dates = array_map(fn ($y) => "$y-03-31", $fy[1]);
            if (!preg_match('/\bIN[A-Z0-9]{10}\b/', $l) && preg_match('/^[\s\d,]*$/', preg_replace(['/\b(19|20)\d{2}\b/', '/' . self::LONG . '|' . self::SHORT . '/', '/\b(Stable|Positive|Negative|Developing|Provisional|Withdrawn|withdrawn|RBI|SEBI|MCA)\b|[();,]/'], ' ', $l))) continue;
            if (!preg_match('/(\d[\d,]*(?:\.\d+)?)\s+(?:' . self::LONG . '|' . self::SHORT . '|Provisional|Withdrawn|\[ICRA\]|CARE\s)/i', $l, $amt) || preg_match('/^(19|20)\d{2}$/', $amt[1])) {
                // Rating wrapped onto another line: the amount is the last plain number before the regulator.
                $plain = preg_replace(['/\bIN[A-Z0-9]{10}\b/', '/\d+(\.\d+)?\s?%/', '/(' . self::MONTHS . ')\s+\d{1,2},?\s*(\d{4})?/i', '/\d{2}[\/-]\d{2}[\/-]\d{4}/', '/\b(19|20)\d{2}\b/', '/\d+-\d+\s*days/i', '/' . self::LONG . '|' . self::SHORT . '/', '/\b(Stable|Positive|Negative|Developing|Withdrawn)\b|[()]/i'], ' ', $l);
                $amt = preg_match('/(\d[\d,]*(?:\.\d+)?)\s*(?:RBI|SEBI|MCA|IRDAI)?\s*\$?\s*$/', trim($plain), $amt2) ? $amt2 : [];
            }
            $nameText = preg_replace(['/\bIN[A-Z0-9]{10}\b/', '/\bNA\b/', '/(' . self::MONTHS . ')\s+\d{1,2},?\s*(\d{4})?/i', '/\d{2}[\/-]\d{2}[\/-]\d{4}/', '/\d+(\.\d+)?\s?%/', '/\d[\d,]*(\.\d+)?/', '/' . self::LONG . '|' . self::SHORT . '/', '/\b(Stable|Positive|Negative|Developing|Provisional|Withdrawn|RBI|SEBI|MCA|withdraw)\b\$?/i', '/[();\-–]+/', '/\s+/'], ' ', $l);
            $long = $this->firstLong($window);
            if (count($dates) >= 2 && $dates[0] > end($dates)) $dates = [$dates[0]];
            $out[] = [
                'isin' => $isin,
                'instrument' => trim($nameText) ?: null,
                'type' => $this->type($nameText . ' ' . $window),
                'coupon' => $coupon,
                'issued_on' => count($dates) >= 2 ? $dates[0] : null,
                'maturity_on' => count($dates) >= 2 || preg_match('/FY/', $l) ? end($dates) : null,
                'amount_crore' => isset($amt[1]) ? (float) str_replace(',', '', $amt[1]) : null,
                'rating' => $long['rating'] ?? $this->firstShort($window),
                'withdrawn' => (bool) preg_match('/withdraw/i', $window),
            ];
            if (count($out) >= 80) break;
        }
        return $out;
    }

    private function ymd(string $date): ?string
    {
        try { return Carbon::parse($date)->toDateString(); } catch (Throwable) { return null; }
    }

    /** "Key financial indicators" (ICRA) / "Brief Financials" (CARE): periods and figures (Rs. crore, % or times). */
    private function financials(array $lines): ?array
    {
        $periodRe = '/FY\s?20\d{2}|March\s+31,\s*20\d{2}|Mar-?\d{2,4}|\d{1,2}M\s?FY\s?\d{2,4}|Q\d\s?FY\s?\d{2,4}|H\d\s?FY\s?\d{2,4}/i';
        foreach ($lines as $i => $l) {
            if (!preg_match('/Key financial indicators|Brief Financials|Key Financial Indicators/i', $l)) continue;
            // Header: the first line (this one or the next three) naming two or more periods.
            for ($h = $i; $h <= $i + 3 && isset($lines[$h]); $h++) {
                preg_match_all($periodRe, $lines[$h], $p);
                if (count($p[0]) >= 1 && (count($p[0]) >= 2 || preg_match('/FY|March/i', $lines[$h]))) break;
            }
            if (!isset($lines[$h]) || !count($p[0])) continue;
            $periods = array_map(fn ($x) => $this->period($x), $p[0]);
            $n = count($periods); $rows = [];
            for ($k = $h + 1; $k < min(count($lines), $h + 25); $k++) {
                $row = $lines[$k];
                if (preg_match('/^(Source|A:|\*|Status of|Any other|Note|Amount in|PAT:)/i', $row)) break;
                if (preg_match('/^(Audited|Accounting|\(Audited\)|Ind-AS)/i', $row)) continue;
                preg_match_all('/(?<=\s|^)(-?\(?[\d,]+(?:\.\d+)?\)?%?|NA|N\.A\.|-)(?=\s|$)/', $row, $v);
                if (count($v[1]) < 1) continue;
                $values = array_slice($v[1], -$n);
                $label = trim(preg_replace('/\s+(-?\(?[\d,]+(?:\.\d+)?\)?%?|NA|N\.A\.|-)(\s+(-?\(?[\d,]+(?:\.\d+)?\)?%?|NA|N\.A\.|-))*\s*$/', '', $row));
                if ($label === '' || mb_strlen($label) > 90 || preg_match('/^\d|^(NA|N\.A\.|-)$/i', $label) || !preg_match('/[A-Za-z]{2}/', $label)) continue;
                $rows[] = ['label' => $label, 'metric' => $this->metric($label), 'unit' => str_contains($values[0] ?? '', '%') || preg_match('/%|\/OI|margin|NPA|CRAR|Stage/i', $label) ? '%' : (preg_match('/times|gearing|coverage|\/OPBDIT|\/TNW|net worth/i', $label) ? 'x' : 'crore'),
                    'values' => array_map(fn ($x) => $this->number($x), array_pad($values, $n, null))];
            }
            if ($rows) return ['periods' => $periods, 'rows' => $rows];
        }
        return null;
    }

    /** "FY2026" → 2025-26, "March 31, 2022" → 2021-22, partial periods kept as written ("Q1 FY2027"). */
    private function period(string $p): array
    {
        $p = trim(preg_replace('/\s+/', ' ', $p));
        $year = null; $partial = (bool) preg_match('/^(Q\d|H\d|\d{1,2}M)/i', $p);
        if (preg_match('/FY\s?(\d{2,4})/i', $p, $m)) $year = (int) (strlen($m[1]) === 2 ? '20' . $m[1] : $m[1]);
        elseif (preg_match('/March\s+31,\s*(20\d{2})/i', $p, $m)) $year = (int) $m[1];
        elseif (preg_match('/Mar-?(\d{2,4})/i', $p, $m)) $year = (int) (strlen($m[1]) === 2 ? '20' . $m[1] : $m[1]);
        return ['label' => $p, 'fy' => $year && !$partial ? ($year - 1) . '-' . substr((string) $year, -2) : null, 'partial' => $partial];
    }

    private function number(?string $v): ?float
    {
        if ($v === null || in_array(strtoupper($v), ['NA', 'N.A.', '-'], true)) return null;
        $neg = str_starts_with($v, '(') || str_starts_with($v, '-');
        $n = (float) str_replace([',', '%', '(', ')', '-'], '', $v);
        return $neg ? -$n : $n;
    }

    private function metric(string $label): ?string
    {
        $l = strtolower($label);
        return match (true) {
            (bool) preg_match('/^(total )?operating income|^operating income|^revenue|^net sales|^total revenue/', $l) => 'revenue',
            (bool) preg_match('/^total income/', $l) => 'total_income',
            (bool) preg_match('/^(pat|profit after tax|net profit)$|^pat\b(?!\s*\/)|^profit after tax/', $l) => 'pat',
            (bool) preg_match('/^(pbildt|opbdit|ebitda|operating profit)$/', $l) => 'ebitda',
            (bool) preg_match('/opbdita?\s*\/\s*oi|pbildt margin|ebitda margin|operating margin/', $l) => 'ebitda_margin',
            (bool) preg_match('/pat\s*\/\s*oi|pat margin/', $l) => 'pat_margin',
            (bool) preg_match('/total debt\s*\/\s*opbdita?|debt\s*\/\s*ebitda/', $l) => 'debt_to_ebitda',
            (bool) preg_match('/return on (average )?(managed )?assets|roma\b|\broa\b/', $l) => 'roa',
            (bool) preg_match('/interest coverage/', $l) => 'interest_coverage',
            (bool) preg_match('/overall gearing|^gearing|managed gearing/', $l) => 'gearing',
            (bool) preg_match('/total outside liabilities/', $l) => 'tol_tnw',
            (bool) preg_match('/managed assets|total assets|assets under management|aum/', $l) => 'total_assets',
            (bool) preg_match('/gross (npa|stage)/', $l) => 'gross_npa',
            (bool) preg_match('/crar|capital adequacy|capital to risk/', $l) => 'crar',
            (bool) preg_match('/return on (average )?(managed )?assets|roma|roa/', $l) => 'roa',
            default => null,
        };
    }

    /** Headings of key rating drivers between two section titles ("Strong parentage – …", "Experienced management: …"). */
    private function drivers(string $flat, string $from, string $to): array
    {
        if (!preg_match('/' . $from . '\s*(.*?)(?=' . $to . '|$)/is', $flat, $m)) return [];
        $section = mb_substr($m[1], 0, 9000);
        preg_match_all('/(?:^|(?<=[.)]\s))([A-Z][\w’\'&,;\/()%+-]*(?:\s[\w’\'&,;\/()%+-]+){1,24}?)\s(?:–|:|-\s)\s*([A-Z].{40,500}?(?:\.(?=\s[A-Z])|$))/su', $section, $items, PREG_SET_ORDER);
        $out = [];
        foreach ($items as $it) {
            $heading = trim($it[1]);
            if (mb_strlen($heading) < 12 || mb_strlen($heading) > 130 || preg_match('/^(The|This|It|ICRA|CARE|However|Further|Also|Further,|Moreover|Nevertheless|In|As|For|While)\b/', $heading)) continue;
            $out[] = ['title' => mb_substr($heading, 0, 180), 'detail' => mb_substr(trim($it[2]), 0, 500)];
            if (count($out) >= 6) break;
        }
        return $out;
    }

    /** Driver headings on lines of their own (newer CARE layout): a short line without a full stop, followed by its paragraph. */
    private function lineDrivers(array $lines, string $from, string $to): array
    {
        $start = null;
        foreach ($lines as $i => $l) if (preg_match($from, $l)) { $start = $i; break; }
        if ($start === null) return [];
        $out = [];
        for ($i = $start + 1; $i < min(count($lines), $start + 200); $i++) {
            $l = $lines[$i];
            if (preg_match($to, $l)) break;
            $prev = $lines[$i - 1];
            $isHeading = mb_strlen($l) >= 12 && mb_strlen($l) <= 120 && !preg_match('/[.:;,]$/', $l) && preg_match('/^[A-Z]/', $l)
                && ($i === $start + 1 || preg_match('/[.)]$/', $prev)) && !preg_match('/CARE Ratings|Press Release|SEBI|Complete definition|^\d/', $l)
                && !preg_match('/^(The|This|It|In|As|For|While|Further|However|Also|Its|With|During|Over)\b/', $l) && preg_match('/^[A-Z]/', $lines[$i + 1] ?? '');
            if (!$isHeading) continue;
            $detail = '';
            for ($j = $i + 1; $j < min(count($lines), $i + 8) && !preg_match($to, $lines[$j]); $j++) { $detail .= ' ' . $lines[$j]; if (mb_strlen($detail) > 420) break; }
            $out[] = ['title' => $l, 'detail' => mb_substr(trim($detail), 0, 500)];
            if (count($out) >= 6) break;
        }
        return $out;
    }

    private function sensitivities(string $flat): array
    {
        $out = [];
        foreach (['positive' => 'Positive factors', 'negative' => 'Negative factors'] as $key => $label) {
            if (preg_match('/' . $label . '\s*[–:-]?\s*(?:Factors that could lead to [^:]+:\s*)?(.{10,700}?)(?=Negative factors|Analytical approach|Detailed description|Liquidity|Positive factors|$)/isu', $flat, $m)) {
                $text = trim(preg_replace('/\s+/', ' ', $m[1]));
                if (!preg_match('/^(Not applicable|NA)\b/i', $text)) $out[$key] = mb_substr($text, 0, 600);
            }
        }
        return $out;
    }

    private function about(string $flat): ?string
    {
        if (!preg_match('/About the (?:company|originator|entity|issuer|trust)(?: and industry)?\s+(?:Industry classification.*?(?=Incorporated|Established|The company|[A-Z][a-z]+ (?:is|was)))?(.{60,1200}?)(?=Key financial indicators|Brief Financials|Status of non-cooperation|About the companies|Particular|$)/is', $flat, $m)) return null;
        return mb_substr(trim($m[1]), 0, 1000);
    }
}
