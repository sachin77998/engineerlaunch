<?php

namespace App\Services\Insights;

use Illuminate\Support\Facades\Http;
use RuntimeException;

/**
 * InstaFinancials API (paid, needs INSTAFINANCIALS_API_KEY): CIN search and InstaBasic (MCA master data, signatories
 * and charges). Ported from the earlier company-check project; responses are mapped best-effort because field names
 * vary between report versions, and the raw response is kept so nothing is lost.
 */
class InstaFinancials
{
    public function configured(): bool
    {
        return filled(config('services.instafinancials.api_key')) && filled(config('services.instafinancials.base_url'));
    }

    /** Companies matching a name (NC = name contains, SW = starts with). */
    public function searchCin(string $term, string $mode = 'NC'): array
    {
        return $this->get('/InstaReports/v1/GetCIN/Search/' . rawurlencode($term) . '/Mode/' . (strtoupper($mode) === 'SW' ? 'SW' : 'NC'));
    }

    /** Current MCA master data, directors/signatories and charges for a CIN. */
    public function basic(string $cin, int $daysToIgnore = 7): array
    {
        return $this->get('/InstaReports/v2/InstaBasic/CompanyCIN/' . rawurlencode(strtoupper($cin)), ['daysToIgnore' => max(0, $daysToIgnore)]);
    }

    /** Any InstaFinancials path (used for InstaSummary financials). */
    public function getPath(string $path, array $query = []): array
    {
        return $this->get('/' . ltrim($path, '/'), $query);
    }

    private function get(string $path, array $query = []): array
    {
        if (!$this->configured()) throw new RuntimeException('InstaFinancials is not configured (set INSTAFINANCIALS_BASE_URL and INSTAFINANCIALS_API_KEY).');
        $response = Http::withHeaders(['user-key' => config('services.instafinancials.api_key'), 'Accept' => 'application/json'])
            ->timeout(90)->get(rtrim(config('services.instafinancials.base_url'), '/') . $path, $query);
        if (!$response->successful()) throw new RuntimeException('InstaFinancials returned HTTP ' . $response->status() . ': ' . mb_substr($response->body(), 0, 300));
        $json = $response->json();
        if (!is_array($json)) throw new RuntimeException('InstaFinancials returned an invalid response.');
        if (isset($json['ErrorResponse']) || isset($json['ErrorCode']) || isset($json['Error'])) {
            $error = $json['ErrorResponse'] ?? $json;
            throw new RuntimeException(sprintf('InstaFinancials rejected the request (%s): %s', $error['ErrorCode'] ?? 'unknown', $error['Message'] ?? $error['Error'] ?? 'Unknown provider error'));
        }
        return $json;
    }

    /** [{name, cin}] from a search response, whatever its nesting. */
    public static function searchResults(array $json): array
    {
        $rows = [];
        array_walk_recursive($json, function () {});
        $walk = function ($node) use (&$walk, &$rows) {
            if (!is_array($node)) return;
            $cin = self::pick($node, ['cin', 'companycin', 'corporateidentificationnumber']);
            $name = self::pick($node, ['companyname', 'name', 'company']);
            if (is_string($cin) && preg_match(CompanyInsights::CIN, strtoupper($cin)) && is_string($name)) $rows[strtoupper($cin)] = ['cin' => strtoupper($cin), 'name' => $name];
            foreach ($node as $child) if (is_array($child)) $walk($child);
        };
        $walk($json);
        return array_values($rows);
    }

    /** Value of the first key whose normalised name is in $names (one level). */
    public static function pick(array $node, array $names)
    {
        foreach ($node as $key => $value) {
            if (in_array(strtolower(preg_replace('/[^a-z0-9]/i', '', (string) $key)), $names, true) && $value !== null && $value !== '') return $value;
        }
        return null;
    }

    /** Recursively finds the first scalar for any of the keys. */
    public static function find(array $json, array $names)
    {
        $found = self::pick($json, $names);
        if ($found !== null && !is_array($found)) return $found;
        foreach ($json as $child) {
            if (is_array($child) && ($value = self::find($child, $names)) !== null) return $value;
        }
        return null;
    }

    /** Lists of people (directors / signatories) anywhere in the response. */
    public static function people(array $json): array
    {
        $people = [];
        $walk = function ($node) use (&$walk, &$people) {
            if (!is_array($node)) return;
            $name = self::pick($node, ['name', 'directorname', 'signatoryname', 'fullname']);
            $din = self::pick($node, ['din', 'dinpan', 'dinorpan', 'directoridentificationnumber']);
            if (is_string($name) && ($din !== null || self::pick($node, ['designation']) !== null)) {
                $people[mb_strtolower($name)] = ['name' => ucwords(mb_strtolower(trim($name))), 'din' => is_scalar($din) && preg_match('/^\d{8}$/', (string) $din) ? (string) $din : null,
                    'designation' => ucwords(mb_strtolower((string) (self::pick($node, ['designation', 'role']) ?? 'Director'))),
                    'appointed_on' => self::pick($node, ['dateofappointment', 'appointmentdate', 'begindate', 'datejoined']),
                    'ceased_on' => self::pick($node, ['dateofcessation', 'cessationdate', 'enddate'])];
            }
            foreach ($node as $child) if (is_array($child)) $walk($child);
        };
        $walk($json);
        return array_values($people);
    }

    /** Registered charges anywhere in the response (charge id, holder, amount, status, dates, assets). */
    public static function charges(array $json): array
    {
        $charges = [];
        $walk = function ($node) use (&$walk, &$charges) {
            if (!is_array($node)) return;
            $id = self::pick($node, ['chargeid', 'srn', 'chargeidentificationnumber']);
            $holder = self::pick($node, ['chargeholdername', 'chargeholder', 'nameofchargeholder', 'lender', 'bankname']);
            // A row is a charge when it has a charge id plus a holder, an amount or the charged assets.
            if ($id !== null && is_scalar($id) && ($holder !== null || self::pick($node, ['amount', 'chargeamount', 'assetsundercharge']) !== null)) {
                $holder = is_scalar($holder) ? $holder : 'Not stated';
                $amount = self::pick($node, ['amount', 'chargeamount', 'amountsecured']);
                $charges[(string) $id] = [
                    'charge_id' => mb_substr((string) $id, 0, 30), 'lender' => mb_substr((string) $holder, 0, 250),
                    'amount' => is_scalar($amount) && is_numeric($clean = preg_replace('/[^\d.]/', '', (string) $amount)) ? (float) $clean : null,
                    'status' => is_scalar($s = self::pick($node, ['status', 'chargestatus'])) ? ucfirst(strtolower((string) $s)) : null,
                    'created_on' => self::pick($node, ['dateofcreation', 'creationdate', 'datecreated']),
                    'modified_on' => self::pick($node, ['dateofmodification', 'modificationdate']),
                    'satisfied_on' => self::pick($node, ['dateofsatisfaction', 'satisfactiondate']),
                    'assets_charged' => is_scalar($assets = self::pick($node, ['assetsundercharge', 'assetscharged', 'propertycharged'])) ? (string) $assets : null,
                ];
            }
            foreach ($node as $child) if (is_array($child)) $walk($child);
        };
        $walk($json);
        return array_values($charges);
    }

    /** Flat "path => value" list of every scalar, for display. */
    public static function flatten(array $json, string $prefix = ''): array
    {
        $out = [];
        foreach ($json as $key => $value) {
            $path = $prefix === '' ? (string) $key : $prefix . ' › ' . $key;
            if (is_array($value)) $out += self::flatten($value, $path);
            elseif ($value !== null && $value !== '') $out[$path] = is_bool($value) ? ($value ? 'Yes' : 'No') : (string) $value;
            if (count($out) >= 400) break;
        }
        return $out;
    }
}
