<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * City names as written in company addresses vary ("Bangalore", "Bengaluru Urban", "Bangalore,", "Banagalore").
 * Groups them under one display name per city for a state: the geography catalogue's official name when it recognises
 * the spelling (aliases such as Bangalore → Bengaluru), otherwise a near-identical spelling of a bigger city (typos one
 * or two letters off), with district suffixes (Urban, Rural, North, South, East, West) folded in.
 */
class CityNames
{
    private const SUFFIX = '/\s+(urban|rural|north|south|east|west|city|district|dist\.?)$/i';
    // Renamed cities: old name (as still written in many addresses) → current official name.
    private const RENAMED = ['bangalore' => 'Bengaluru', 'mysore' => 'Mysuru', 'mangalore' => 'Mangaluru', 'belgaum' => 'Belagavi', 'bellary' => 'Ballari',
        'gulbarga' => 'Kalaburagi', 'bijapur' => 'Vijayapura', 'hubli' => 'Hubballi', 'shimoga' => 'Shivamogga', 'tumkur' => 'Tumakuru',
        'gurgaon' => 'Gurugram', 'bombay' => 'Mumbai', 'madras' => 'Chennai', 'calcutta' => 'Kolkata', 'poona' => 'Pune', 'trivandrum' => 'Thiruvananthapuram',
        'cochin' => 'Kochi', 'baroda' => 'Vadodara', 'allahabad' => 'Prayagraj', 'aurangabad' => 'Chhatrapati Sambhajinagar', 'pondicherry' => 'Puducherry',
        'calicut' => 'Kozhikode', 'trichy' => 'Tiruchirappalli', 'vizag' => 'Visakhapatnam'];

    /** @return array<string, string[]> display name => spellings, biggest cities first */
    public function groups(string $state, string $country = ''): array
    {
        return Cache::remember('city-names:' . md5($country . '|' . $state), now()->addHours(12), function () use ($state, $country) {
            $raw = DB::table('company_facilities')->where('state', $state)->when($country !== '', fn ($q) => $q->where('country', $country))
                ->whereNotNull('city')->where('city', '!=', '')->selectRaw('city, count(*) n')->groupBy('city')->pluck('n', 'city');
            $geo = app(JobGeography::class);
            $resolve = new \ReflectionMethod($geo, 'resolve');
            $resolve->setAccessible(true);
            $groups = []; $sizes = [];
            foreach ($raw as $city => $n) {
                $clean = trim(preg_replace('/\s+/', ' ', trim((string) $city, " ,.-")));
                if ($clean === '' || preg_match('/\d|sector|plot|road|,/i', $clean)) continue;   // address fragments, not cities
                $base = trim(preg_replace(self::SUFFIX, '', $clean));
                $places = $resolve->invoke($geo, $base . ', ' . $state . ($country ? ', ' . $country : ''), $country);
                $official = collect($places)->pluck('city')->filter()->first();
                $name = self::RENAMED[mb_strtolower($base)] ?? self::RENAMED[mb_strtolower((string) $official)] ?? ($official ? Str::title($official) : Str::title(mb_strtolower($base)));
                $groups[$name][] = (string) $city;
                $sizes[$name] = ($sizes[$name] ?? 0) + $n;
            }
            // Typos: a much smaller spelling with the same first letter, one letter away from any spelling of a bigger city
            // (two for long names), joins it — "Banagalore" → Bengaluru (via "Bangalore"), but never Mangaluru → Bengaluru.
            arsort($sizes);
            $names = array_keys($sizes);
            $near = function (string $a, string $b): bool {
                $a = mb_strtolower(trim(preg_replace(self::SUFFIX, '', $a), ' ,')); $b = mb_strtolower(trim(preg_replace(self::SUFFIX, '', $b), ' ,'));
                if ($a === '' || $b === '' || $a[0] !== $b[0]) return false;
                return levenshtein($a, $b) <= (min(strlen($a), strlen($b)) >= 8 ? 2 : 1);
            };
            foreach (array_reverse($names) as $small) {
                if (!isset($groups[$small]) || mb_strlen($small) < 5) continue;
                foreach ($names as $big) {
                    if ($big === $small || !isset($groups[$big]) || $sizes[$big] <= $sizes[$small] * 3) continue;
                    if (collect(array_merge([$big], $groups[$big]))->contains(fn ($s) => $near($small, $s))) { $groups[$big] = array_merge($groups[$big], $groups[$small]); unset($groups[$small]); break; }
                }
            }
            uksort($groups, fn ($a, $b) => strcasecmp($a, $b));
            return $groups;
        });
    }

    /** Every spelling recorded for a chosen city (the city itself when unknown). @return string[] */
    public function variants(string $city, string $state = '', string $country = ''): array
    {
        if ($state === '') return [$city];
        $groups = $this->groups($state, $country);
        foreach ($groups as $name => $spellings) if (strcasecmp($name, $city) === 0 || in_array($city, $spellings, true)) return array_values(array_unique(array_merge([$name], $spellings)));
        return [$city];
    }
}
