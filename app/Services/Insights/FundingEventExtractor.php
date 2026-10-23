<?php

namespace App\Services\Insights;

// Reads funding events from rating-report text: promoter/group infusions (equity, CCDs, unsecured loans),
// bond/NCD/fund raises, loan repayments and prepayments, debt tie-ups, project capex and limit utilisation.
// Each event keeps the sentence it came from, so the page can show the evidence.
class FundingEventExtractor
{
    private const AMT = '(?:Rs\.?|INR|₹)\s*([\d,]+(?:\.\d+)?)\s*(crore|cr|lakh)';
    private const INSTRUMENTS = [
        'ccd' => '/compulsor(?:y|ily)\s+convertible\s+debentures?|\bCCDs?\b/i',
        'ocd' => '/optionally\s+convertible\s+debentures?|\bOCDs?\b/i',
        'ncd' => '/non[- ]convertible\s+debentures?|\bNCDs?\b|\bbonds?\b/i',
        'preference' => '/preference\s+shares?|\bCCPS\b/i',
        'equity' => '/\bequity\b|rights issue|\bIPO\b|\bQIP\b|share capital/i',
        'unsecured_loan' => '/unsecured\s+loans?|inter[- ]corporate\s+(?:deposits?|loans?)|\bICDs?\b/i',
        'term_loan' => '/term\s+(?:loans?|debt)/i',
        'commercial_paper' => '/commercial\s+papers?/i',
    ];

    public function extract(string $text): array
    {
        $flat = preg_replace('/\s+/u', ' ', $text);
        $sentences = preg_split('/(?<=[.;])\s+(?=[A-Z(])/u', $flat) ?: [];
        $events = [];
        foreach ($sentences as $s) {
            if (mb_strlen($s) < 25 || mb_strlen($s) > 900) continue;
            $low = mb_strtolower($s);
            // Money brought in: "fund infusion of Rs. 320 crore in the form of CCDs from International Tractors Limited".
            if (preg_match('/(?:infus(?:ion|ed)|brought\s+in|raised|mobili[sz]ed|issued?|subscri(?:bed|ption)|capital\s+raise|fund(?:ing)?\s+support)\D{0,60}?' . self::AMT . '/iu', $s, $m)
                || preg_match('/' . self::AMT . '[^.;]{0,80}?(?:infus|raised|brought\s+in|through\s+(?:the\s+)?issue)/iu', $s, $m)) {
                $events[] = $this->event('inflow', $s, $this->amount($m[1], $m[2]), [
                    'instrument' => $this->instrument($s), 'from' => $this->party($s),
                ]);
            }
            // Debt paid off: "utilised mainly towards full repayment of bank long-term debt", "prepaid term loans of Rs. 50 crore".
            if (preg_match('/(full|complete|partial|part)?\s*(?:re|pre)pa(?:id|yment|y)\w*\s+(?:of\s+)?(?:the\s+|its\s+|all\s+)?(?:outstanding\s+)?(bank\s+)?(long[- ]term\s+debt|term\s+loans?|term\s+debt|debt|borrowings|loans?|NCDs?|promoters?\W?\s+loans?)/iu', $s, $m)
                || preg_match('/(no\s+long[- ]term\s+debt|debt[- ]free|bank\s+term\s+loan\s+(?:has\s+been\s+)?fully\s+repaid)/iu', $s, $m)) {
                $amt = preg_match('/' . self::AMT . '/iu', $s, $a) ? $this->amount($a[1], $a[2]) : null;
                $events[] = $this->event('repayment', $s, $amt, ['scope' => strtolower(trim(($m[1] ?? '') ?: (str_contains($low, 'partial') ? 'partial' : 'full'))), 'debt' => trim($m[3] ?? $m[1] ?? 'debt')]);
            }
            // New borrowing arranged: "term debt ... has already been tied up", "sanctioned term loan of Rs 220 crore".
            if (preg_match('/(tied[- ]up|sanctioned|availed|drawn\s+down|disbursed|enhanc(?:ed|ement)\s+(?:in\s+)?(?:the\s+)?(?:rated\s+)?(?:amount|limits?))/iu', $s) && preg_match('/term\s+(?:debt|loans?)|working\s+capital|limits?|loans?|debt\s+funding/iu', $s)) {
                $amt = preg_match('/' . self::AMT . '/iu', $s, $a) ? $this->amount($a[1], $a[2]) : null;
                $events[] = $this->event('borrowing', $s, $amt, ['instrument' => $this->instrument($s) ?? 'term_loan']);
            }
            // Capex: "foundry unit ... at an estimated cost of about Rs. 330 crore ... funded by debt-to-equity mix of 3:1".
            if (preg_match('/(?:project|capex|expansion|plant|unit|facility|capacity)[^.;]{0,120}?(?:estimated\s+)?cost\s+of\s+(?:about\s+|around\s+|~)?' . self::AMT . '/iu', $s, $m)) {
                $events[] = $this->event('capex', $s, $this->amount($m[1], $m[2]), ['funding_mix' => preg_match('/debt[- ]to[- ]equity\s+(?:mix\s+|ratio\s+)?of\s+(\d+(?:\.\d+)?\s*:\s*\d+(?:\.\d+)?)/iu', $s, $d) ? str_replace(' ', '', $d[1]) : null]);
            }
            // Working-capital use: "average utilisation of the fund-based limits was 62%".
            if (preg_match('/(average|peak|maximum)\s+(?:monthly\s+)?(?:fund[- ]based\s+)?(?:working[- ]capital\s+)?(?:limit\s+)?utili[sz]ation[^.;%]{0,90}?(\d{1,3}(?:\.\d+)?)\s*%/iu', $s, $m)) {
                $events[] = $this->event('utilisation', $s, null, ['kind' => strtolower($m[1]), 'percent' => (float) $m[2]]);
            }
            // Cash and undrawn lines: "cash and cash equivalents of Rs. 61 crore and buffer in fund-based working capital lines of Rs. 10 crore".
            if (preg_match('/(?:cash\s+(?:and\s+(?:cash\s+equivalents|bank\s+balances?|liquid\s+investments))?|total\s+liquidity|liquid\s+(?:investments|assets))[^.;]{0,40}?' . self::AMT . '/iu', $s, $m)) {
                $buffer = preg_match('/(?:buffer|undrawn|unutili[sz]ed|headroom)[^.;]{0,60}?' . self::AMT . '/iu', $s, $b) ? $this->amount($b[1], $b[2]) : null;
                $events[] = $this->event('liquidity', $s, $this->amount($m[1], $m[2]), ['undrawn_lines' => $buffer]);
            }
            // Debt obligations ahead: "repayment obligations of about Rs. 17.5 crore in FY2025".
            if (preg_match('/repayment\s+obligations?\s+of\s+(?:about\s+|around\s+|~)?' . self::AMT . '(?:[^.;]{0,30}?(FY\s?\d{2,4}|20\d\d))?/iu', $s, $m)) {
                $events[] = $this->event('obligation', $s, $this->amount($m[1], $m[2]), ['year' => isset($m[3]) ? str_replace(' ', '', $m[3]) : null]);
            }
        }
        // One sentence can match several patterns of the same kind; keep the first of each kind per sentence.
        // The same figure is often repeated in the rationale and the drivers; keep one event per type and amount.
        return collect($events)->unique(fn ($e) => $e['type'] . '|' . ($e['amount_crore'] !== null ? $e['amount_crore'] . '|' . ($e['instrument'] ?? '') : md5($e['evidence'])))->values()->take(25)->all();
    }

    /** The rationale paragraph(s) between "Rationale" and the detailed drivers section. */
    public function rationale(string $text): ?string
    {
        $flat = preg_replace('/\s+/u', ' ', $text);
        if (!preg_match('/\b(?:Detailed\s+)?Rationale(?:\s+(?:and|&)\s+key\s+rating\s+drivers)?\s+(.{80,6000}?)(?=Key rating drivers and their description|Detailed description of (?:the )?key rating drivers|Key Rating Drivers|Analytical approach|Credit strengths|Key strengths|$)/iu', $flat, $m)) return null;
        return mb_substr(trim($m[1]), 0, 5000);
    }

    public function liquidity(string $text): ?string
    {
        $flat = preg_replace('/\s+/u', ' ', $text);
        if (!preg_match('/Liquidity(?:\s+position)?\s*[:–-]?\s*(?:Superior|Strong|Adequate|Stretched|Poor)\s+(.{40,1500}?)(?=Rating sensitivities|Assumptions|Environment|Applicable criteria|Analytical approach|About the|$)/iu', $flat, $m)) return null;
        return mb_substr(trim($m[1]), 0, 1500);
    }

    private function event(string $type, string $sentence, ?float $amount, array $extra): array
    {
        return ['type' => $type, 'amount_crore' => $amount, 'evidence' => mb_substr(trim($sentence), 0, 600)] + array_filter($extra, fn ($v) => $v !== null && $v !== '');
    }

    private function amount(string $n, string $unit): float
    {
        $v = (float) str_replace(',', '', $n);
        return round(strtolower($unit) === 'lakh' ? $v / 100 : $v, 2);
    }

    private function instrument(string $s): ?string
    {
        foreach (self::INSTRUMENTS as $key => $re) if (preg_match($re, $s)) return $key;
        return null;
    }

    // "from International Tractors Limited (ITL; rated ...)" → "International Tractors Limited".
    private function party(string $s): ?string
    {
        if (preg_match('/\b(?:from|by)\s+(?:its\s+|the\s+)?((?:promoters?|parent|group|holding\s+company|sponsor|institutional\s+investors?)\b|(?:[A-Z][A-Za-z&.\-]+\s){0,6}(?:Limited|Ltd\.?|Group|Bank|Fund|Partners|Capital|LLP|Inc\.?|Corporation|[A-Z]{2,6}))/u', $s, $m)) {
            return trim(rtrim($m[1], ' .'));
        }
        return null;
    }
}
