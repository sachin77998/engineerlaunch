<?php

namespace App\Services\Insights;

use Carbon\Carbon;

/**
 * Reads figures from the text of MCA filings (AOC-4 / AOC-4 XBRL financial statements and MGT-7 annual returns).
 * Works on text extracted from the PDF: each figure is the first amount after its label (current period; the
 * previous period follows it in the form). Labels cover both the old Schedule III and Ind AS wording.
 */
class FilingParser
{
    public const AOC4_LABELS = [
        'revenue' => ['revenue from operations', 'net sales', 'turnover'],
        'other_income' => ['other income'],
        'total_income' => ['total income', 'total revenue'],
        'employee_cost' => ['employee benefits? expenses?'],
        'finance_cost' => ['finance costs?', 'interest expenses?'],
        'depreciation' => ['depreciation,? (?:depletion )?and amorti[sz]ation expenses?', 'depreciation'],
        'total_expenses' => ['total expenses'],
        'profit_before_tax' => ['profit\s*/?\s*\(?loss\)? before (?:exceptional .{0,40}? and )?tax', 'profit before tax'],
        'tax_expense' => ['total tax expenses?', 'tax expenses?'],
        'profit_after_tax' => ['profit\s*/?\s*\(?loss\)? for the (?:period|year)', 'net profit after tax', 'profit after tax'],
        'share_capital' => ['(?:equity )?share capital'],
        'reserves' => ['reserves and surplus', 'other equity'],
        'net_worth' => ['net ?worth', 'total equity(?! and)', "shareholders'? funds"],
        'long_term_borrowings' => ['long[- ]term borrowings', 'non[- ]current borrowings', 'borrowings \(non[- ]current\)'],
        'short_term_borrowings' => ['short[- ]term borrowings', 'current borrowings', 'borrowings \(current\)'],
        'trade_payables' => ['trade payables'],
        'current_liabilities' => ['total current liabilities'],
        'trade_receivables' => ['trade receivables'],
        'inventories' => ['inventories'],
        'cash_and_bank' => ['cash and cash equivalents', 'cash and bank balances'],
        'current_assets' => ['total current assets'],
        'fixed_assets' => ['property,? plant and equipment', 'tangible assets'],
        'total_assets' => ['total assets'],
        'eps' => ['basic earnings? (?:\(loss\) )?per (?:equity )?share', 'basic eps'],
    ];

    public function parseAoc4(string $text): array
    {
        $flat = $this->flatten($text);
        $scale = $this->scale($flat);
        $data = ['cin' => $this->cin($flat), 'company_name' => $this->companyName($flat), 'financial_year' => null, 'period_end' => null, 'unit' => $scale[1]];
        if ($end = $this->periodEnd($flat)) {
            $data['period_end'] = $end->toDateString();
            $data['financial_year'] = self::financialYear($end);
        }
        foreach (self::AOC4_LABELS as $field => $labels) {
            foreach ($labels as $label) {
                $value = $this->amountAfter($flat, $label);
                if ($value !== null) { $data[$field] = $field === 'eps' ? $value : round($value * $scale[0], 2); break; }
            }
        }
        // Signing directors in AOC-4 XBRL: first / middle / last name, designation, DIN.
        $data['officers'] = [];
        if (preg_match_all('/first name of director\s*(.*?)\s*middle name of director\s*(.*?)\s*last name of director\s*(.*?)\s*designation of director\s*(.*?)\s*director identification number of director\s*(\d{8})/i', $flat, $matches, PREG_SET_ORDER)) {
            foreach ($matches as $m) {
                $name = trim(preg_replace('/\s+/', ' ', "{$m[1]} {$m[2]} {$m[3]}"));
                if ($name !== '') $data['officers'][] = ['din' => $m[5], 'name' => ucwords(strtolower($name)), 'designation' => ucwords(strtolower(trim($m[4]))) ?: 'Director', 'appointed_on' => null];
            }
        }
        // Products / services: "Product or service category (ITC 4 digit) code 8708  Description ... Parts of motor vehicles  Turnover ... 1,234".
        $data['activities'] = [];
        if (preg_match_all('/product or service category \(itc \d digit\) code\s*(\d{4,8})\s*description of product or service category\s*(.{3,150}?)\s*turnover of (?:the )?product or service category\s*([\d,]+(?:\.\d+)?)/i', $flat, $matches, PREG_SET_ORDER)) {
            foreach ($matches as $m) {
                $turnover = (float) str_replace(',', '', $m[3]) * $scale[0];
                $data['activities'][] = ['code' => $m[1], 'description' => trim($m[2]), 'main_activity' => 'ITC ' . $m[1],
                    'turnover_percentage' => !empty($data['revenue']) ? round(min(100, $turnover / $data['revenue'] * 100), 2) : null];
            }
        }
        // Related-party transactions (AOC-2 section of AOC-4).
        $data['related_parties'] = [];
        if (preg_match_all('/name\(?s?\)? of (?:the )?related party\s*(.{2,120}?)\s*nature of (?:the )?related party relationship\s*(.{2,120}?)\s*(?:description of )?nature of (?:the )?(?:material )?contracts?\s*\/?\s*arrangements?\s*\/?\s*transactions?\s*(.{2,200}?)(?=\s*(?:duration|salient|amount|date|name\(?s?\)? of (?:the )?related party|$))(?:.{0,200}?amount[^0-9]{0,60}([\d,]+(?:\.\d+)?))?/i', $flat, $matches, PREG_SET_ORDER)) {
            foreach (array_slice($matches, 0, 100) as $m) {
                $data['related_parties'][] = ['party_name' => trim($m[1]), 'relationship' => trim($m[2]), 'transaction_description' => trim($m[3]),
                    'amount' => isset($m[4]) && $m[4] !== '' ? round((float) str_replace(',', '', $m[4]) * $scale[0], 2) : null];
            }
        }
        if (preg_match('/type of industry\s*[:\-]?\s*(.{3,60}?)(?=\s{2,}|\s+(?:date|whether|category|corporate|registration)\b)/i', $flat, $m)) $data['industry_type'] = trim($m[1]);
        if (preg_match('/category\s*\/\s*sub-?category of (?:the )?company\s*[:\-]?\s*(.{3,80}?)(?=\s{2,}|\s+(?:date|whether|type|corporate|registration)\b)/i', $flat, $m)) $data['company_category'] = trim($m[1]);
        if (preg_match('/description of presentation currency\s*[:\-]?\s*([A-Z]{3})/', $flat, $m)) $data['currency'] = $m[1];
        $data['is_consolidated'] =(bool) preg_match('/consolidated (?:balance sheet|financial statements?)/i', $flat) && !preg_match('/standalone/i', $flat);
        return $data;
    }

    public function parseMgt7(string $text): array
    {
        $flat = $this->flatten($text);
        $data = ['cin' => $this->cin($flat), 'company_name' => $this->companyName($flat), 'financial_year' => null];
        if ($end = $this->periodEnd($flat)) $data['financial_year'] = self::financialYear($end);
        $data['authorised_capital'] = $this->amountAfter($flat, 'total (?:amount of )?authori[sz]ed capital') ?? $this->amountAfter($flat, 'authori[sz]ed (?:share )?capital');
        $data['paid_up_capital'] = $this->amountAfter($flat, 'total (?:amount of )?paid[- ]up capital') ?? $this->amountAfter($flat, 'paid[- ]up (?:share )?capital');
        // "Turnover and net worth of the company (as defined in the Act)": (i) Turnover  (ii) Net worth.
        $data['turnover'] = $this->amountAfter($flat, '\(?i\)?\s*turnover') ?? $this->amountAfter($flat, 'turnover');
        $data['net_worth'] = $this->amountAfter($flat, '\(?ii\)?\s*net worth of the company') ?? $this->amountAfter($flat, 'net worth');
        if (preg_match('/\(PAN\)[^A-Z]{0,60}\b([A-Z]{5}\d{4}[A-Z])\b/', $flat, $m)) $data['pan'] = $m[1];
        // Principal business activities: "1  C  Manufacturing  C7  Metal products ...  85.6"
        $data['activities'] = [];
        if (preg_match_all('/\b\d{1,2}\s+([A-U])\s+([A-Z][A-Za-z ,&\-]{3,60}?)\s+([A-U]\d{1,2})\s+([A-Za-z][A-Za-z ,&\-\/()]{3,120}?)\s+(\d{1,3}\.\d{1,2})\b/', $flat, $matches, PREG_SET_ORDER)) {
            foreach ($matches as $m) {
                if ((float) $m[5] <= 100) $data['activities'][] = ['code' => $m[3], 'description' => trim($m[4]), 'main_activity' => trim($m[2]), 'turnover_percentage' => (float) $m[5]];
            }
        }
        if (preg_match('/(?:e-?mail id|email address)[^@]{0,30}?\s([\w.+-]+@[\w-]+\.[\w.-]+)/i', $flat, $m)) $data['email'] = strtolower($m[1]);
        if (preg_match('/(?:total )?number of (?:members|shareholders)[^0-9]{0,40}([\d,]+)/i', $flat, $m)) $data['members_count'] = (int) str_replace(',', '', $m[1]);
        if (preg_match('/registered office address\s*[:\-]?\s*(.{10,250}?)(?=\s+(?:latitude|e-?mail|telephone|phone|website|\(c\)|\(d\)|\*|[a-z]\)))/i', $flat, $m)) $data['registered_address'] = trim($m[1], " ,");
        // Directors and key managerial personnel: "DIN/PAN  NAME  DESIGNATION  ... date of appointment".
        $data['officers'] = [];
        if (preg_match_all('/\b(\d{8}|[A-Z]{5}\d{4}[A-Z])\s+([A-Z][A-Za-z .\']{2,60}?)\s+(managing director|whole[- ]?time director|independent director|nominee director|additional director|director|cfo|ceo|company secretary|manager)\b(?:[^0-9]{0,40}(\d{2}\/\d{2}\/\d{4}))?/i', $flat, $matches, PREG_SET_ORDER)) {
            foreach ($matches as $m) {
                $data['officers'][] = ['din' => ctype_digit($m[1]) ? $m[1] : null, 'name' => ucwords(strtolower(trim($m[2]))), 'designation' => ucwords(strtolower($m[3])),
                    'appointed_on' => isset($m[4]) ? $this->date($m[4])?->toDateString() : null];
            }
        }
        return $data;
    }

    /**
     * Every "label line followed by value line" pair in the filing (as in Aoc4DynamicParser), so fields without a
     * dedicated column are still visible. Keys are snake_case labels; at most 400 pairs.
     */
    public function fields(string $text): array
    {
        $lines = array_values(array_filter(array_map('trim', preg_split('/\R/u', $text)), fn ($l) => $l !== ''));
        $fields = [];
        for ($i = 0; $i < count($lines) - 1 && count($fields) < 400; $i++) {
            $label = $lines[$i];
            $value = $lines[$i + 1];
            if (!preg_match('/\p{L}{3}/u', $label) || mb_strlen($label) > 120 || mb_strlen($value) > 200) continue;
            // Values are figures, dates, codes or short answers; a long sentence is the next label, not a value.
            if (!preg_match('/^[\d(\-₹]|^[A-Z0-9]{6,}$|^(yes|no|nil|na)$/iu', $value) && str_word_count($value) > 6) continue;
            $key = trim(preg_replace('/[^a-z0-9]+/', '_', mb_strtolower($label)), '_');
            if ($key !== '' && !isset($fields[$key])) { $fields[$key] = $value; $i++; }
        }
        return $fields;
    }

    public static function financialYear(Carbon $end): string
    {
        $endYear = $end->month <= 3 ? $end->year : $end->year + 1;
        return ($endYear - 1) . '-' . substr((string) $endYear, 2);
    }

    private function flatten(string $text): string
    {
        return trim(preg_replace('/\s+/u', ' ', str_replace(["\u{00A0}", '₹'], [' ', ' '], $text)));
    }

    /** [multiplier, label] from "(Amount in Rs. lakhs)" style headers. */
    private function scale(string $text): array
    {
        // AOC-4 XBRL states it explicitly: "Level of rounding used in financial statements  Lakhs".
        if (preg_match('/level of rounding used in financial statements\s*[:\-]?\s*(actual|crores?|lakhs?|lacs?|millions?|thousands?|ten lakhs)/i', $text, $m)) {
            $unit = strtolower($m[1]);
            return match (true) {
                $unit === 'actual' => [1, 'rupees'],
                str_starts_with($unit, 'crore') => [1e7, 'crores'],
                $unit === 'ten lakhs' => [1e6, 'ten lakhs'],
                str_starts_with($unit, 'la') => [1e5, 'lakhs'],
                str_starts_with($unit, 'million') => [1e6, 'millions'],
                default => [1e3, 'thousands'],
            };
        }
        if (preg_match('/(?:amounts?|figures?|rupees|rs\.?|inr|₹)\s*(?:are\s*)?(?:in\s*)?(?:rs\.?|inr|₹)?\s*\(?\s*(crores?|lakhs?|lacs?|millions?|thousands?)\b/i', substr($text, 0, 20000), $m)) {
            $unit = strtolower($m[1]);
            return match (true) {
                str_starts_with($unit, 'crore') => [1e7, 'crores'],
                str_starts_with($unit, 'la') => [1e5, 'lakhs'],
                str_starts_with($unit, 'million') => [1e6, 'millions'],
                default => [1e3, 'thousands'],
            };
        }
        return [1, 'rupees'];
    }

    private function cin(string $text): ?string
    {
        return preg_match('/\b([LU]\d{5}[A-Z]{2}\d{4}[A-Z]{3}\d{6})\b/', $text, $m) ? $m[1] : null;
    }

    private function companyName(string $text): ?string
    {
        if (preg_match('/name of (?:the )?company\s*[:\-]?\s*(.{3,120}?\b(?:limited|ltd\.?|llp|private limited))/i', $text, $m)) return trim($m[1]);
        return null;
    }

    private function periodEnd(string $text): ?Carbon
    {
        foreach (['/(?:date of (?:the )?balance sheet|end date of reporting period|date of end of reporting period|financial year end(?:ed|ing)?(?: date)?|to\s*date|year ended)\s*[:\-]?\s*(\d{2}[\/\-.]\d{2}[\/\-.]\d{4})/i',
                  '/\bto\s+(\d{2}[\/\-.]\d{2}[\/\-.]\d{4})/i', '/(31[\/\-.]03[\/\-.]\d{4})/'] as $pattern) {
            if (preg_match($pattern, $text, $m) && ($date = $this->date($m[1]))) return $date;
        }
        return null;
    }

    private function date(string $value): ?Carbon
    {
        try { return Carbon::createFromFormat('d/m/Y', str_replace(['-', '.'], '/', $value))->startOfDay(); } catch (\Throwable) { return null; }
    }

    /** First amount within ~120 characters after the label, skipping note numbers ("Note 21") and the column header. */
    private function amountAfter(string $text, string $label): ?float
    {
        // Letter boundaries instead of \b: labels may start or end with brackets ("(ii) Net worth", "Borrowings (current)").
        if (!preg_match('~(?<![a-z])' . $label . '(?![a-z])(.{0,140})~iu', $text, $m)) return null;
        preg_match_all('/\(?-?\s*\d[\d,]*(?:\.\d+)?\)?/', $m[1], $tokens);
        $tokens = array_values(array_filter($tokens[0], fn ($t) => preg_match('/\d/', $t)));
        foreach ($tokens as $i => $token) {
            $digits = str_replace([',', '(', ')', '-', ' '], '', $token);
            // A bare 1–2 digit number followed by another amount is a note reference.
            if ($i < count($tokens) - 1 && preg_match('/^\d{1,2}$/', $digits)) continue;
            // Four-digit years in headers ("2024 2023") are not amounts.
            if (preg_match('/^(19|20)\d{2}$/', $digits) && !str_contains($token, ',')) continue;
            $value = (float) $digits;
            return (str_contains($token, '(') || str_contains($token, '-')) ? -$value : $value;
        }
        return null;
    }
}
