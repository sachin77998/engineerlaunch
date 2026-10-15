<?php
namespace App\Services\Insights;

/** Uploaded rating evidence remains unverified and never becomes an AOC-4 statement. */
class RatingReportParser
{
    public function parse(string $text, string $companyName): array
    {
        $first = explode("\f", $text)[0];
        $header = mb_substr(preg_split('/Summary of rating action|Facilities\s*\/\s*Instruments|Rationale/i', $first)[0], 0, 1800);
        $normalize = fn ($value) => trim(preg_replace('/[^a-z0-9]+/', ' ', strtolower($value)));
        // Match the subject heading, not another company mentioned in the rationale.
        $identity = strlen($normalize($companyName)) >= 5 && str_contains(' '.$normalize($header).' ', ' '.$normalize($companyName).' ');
        $agency = preg_match('/\bICRA\b/i', $first) ? 'ICRA' : (preg_match('/CARE Ratings|careedge|CARE has withdrawn/i', $first) ? 'CARE Ratings' : null);
        $date = null;
        if (preg_match('/\b(January|February|March|April|May|June|July|August|September|October|November|December)\s+(\d{1,2}),?\s+(20\d{2})\b/i', $header, $m)) {
            $parsed = \DateTimeImmutable::createFromFormat('!F j Y', "$m[1] $m[2] $m[3]");
            if ($parsed && $parsed->format('j') === (string)(int)$m[2]) $date = $parsed->format('Y-m-d');
        }
        $summary = preg_split('/Rationale/i', $first)[0];
        return [
            'kind'=>'rating_report', 'agency'=>$agency, 'report_date'=>$date,
            'subject_matches'=>$identity,
            'withdrawal_mentioned'=>(bool)preg_match('/\bwithdrawn|withdrawal\b/i', $summary),
            'summary_excerpt'=>mb_substr(trim($summary), 0, 5000),
            'verification'=>'unverified_upload',
            'notice'=>'Historical document evidence only. Confirm the issuer, facility, latest agency action and authenticity before relying on a rating. Withdrawal is not a default or proof of fraud.',
        ];
    }
}
