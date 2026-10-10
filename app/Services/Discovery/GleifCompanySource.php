<?php

namespace App\Services\Discovery;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use RuntimeException;

/**
 * Registered legal entities from GLEIF (Global Legal Entity Identifier Foundation, CC0, no API key):
 * ~4 lakh Indian companies, ~3.6 lakh US, all of Europe and the UAE, searchable by country and city.
 */
class GleifCompanySource
{
    private const URL = 'https://api.gleif.org/api/v1/lei-records';

    // Alternate spellings searched together for one city.
    public const CITY_ALIASES = [
        'gurugram' => ['gurugram', 'gurgaon'], 'bengaluru' => ['bengaluru', 'bangalore'], 'mumbai' => ['mumbai', 'bombay'],
        'chennai' => ['chennai', 'madras'], 'kolkata' => ['kolkata', 'calcutta'], 'mysuru' => ['mysuru', 'mysore'],
        'mohali' => ['mohali', 'sas nagar', 's.a.s nagar'], 'mangaluru' => ['mangaluru', 'mangalore'], 'vadodara' => ['vadodara', 'baroda'],
        'chhatrapati sambhajinagar' => ['aurangabad', 'sambhajinagar'], 'thiruvananthapuram' => ['thiruvananthapuram', 'trivandrum'],
        'kochi' => ['kochi', 'cochin', 'ernakulam'], 'puducherry' => ['puducherry', 'pondicherry'], 'prayagraj' => ['prayagraj', 'allahabad'],
    ];

    private const INDIAN_STATES = [
        'AP' => 'Andhra Pradesh', 'AR' => 'Arunachal Pradesh', 'AS' => 'Assam', 'BR' => 'Bihar', 'CT' => 'Chhattisgarh', 'CG' => 'Chhattisgarh',
        'GA' => 'Goa', 'GJ' => 'Gujarat', 'HR' => 'Haryana', 'HP' => 'Himachal Pradesh', 'JH' => 'Jharkhand', 'KA' => 'Karnataka', 'KL' => 'Kerala',
        'MP' => 'Madhya Pradesh', 'MH' => 'Maharashtra', 'MN' => 'Manipur', 'ML' => 'Meghalaya', 'MZ' => 'Mizoram', 'NL' => 'Nagaland',
        'OR' => 'Odisha', 'OD' => 'Odisha', 'PB' => 'Punjab', 'RJ' => 'Rajasthan', 'SK' => 'Sikkim', 'TN' => 'Tamil Nadu', 'TG' => 'Telangana',
        'TS' => 'Telangana', 'TR' => 'Tripura', 'UP' => 'Uttar Pradesh', 'UT' => 'Uttarakhand', 'UK' => 'Uttarakhand', 'WB' => 'West Bengal',
        'DL' => 'Delhi', 'CH' => 'Chandigarh', 'JK' => 'Jammu and Kashmir', 'LA' => 'Ladakh', 'PY' => 'Puducherry', 'AN' => 'Andaman and Nicobar Islands',
        'DN' => 'Dadra and Nagar Haveli and Daman and Diu', 'DH' => 'Dadra and Nagar Haveli and Daman and Diu', 'LD' => 'Lakshadweep',
    ];

    /**
     * One page of ACTIVE entities. With $city, only entities whose address mentions that city are kept and the city is
     * recorded as their location (so a Manesar address counts for Manesar even if the postal city says Gurgaon).
     * @return array{rows: array, last_page: int}
     */
    public function fetch(string $countryCode, ?string $city, int $page, int $size = 200): array
    {
        $query = ['filter[entity.legalAddress.country]' => strtoupper($countryCode), 'page[size]' => $size, 'page[number]' => $page];
        if ($city) $query['filter[fulltext]'] = $city;
        $response = Http::withHeaders(['User-Agent' => config('discovery.user_agent'), 'Accept' => 'application/vnd.api+json'])
            ->timeout(60)->retry(3, 5000)->get(self::URL, $query);
        if (!$response->successful()) throw new RuntimeException('GLEIF returned HTTP ' . $response->status());

        $countryName = config('discovery.countries.' . strtoupper($countryCode) . '.name') ?? strtoupper($countryCode);
        $needles = $city ? (self::CITY_ALIASES[Str::lower($city)] ?? [Str::lower($city)]) : [];
        $rows = [];
        foreach ($response->json('data', []) as $record) {
            $entity = $record['attributes']['entity'] ?? [];
            if (($entity['status'] ?? '') !== 'ACTIVE') continue;
            $name = trim($entity['legalName']['name'] ?? '');
            if ($name === '' || mb_strlen($name) > 240) continue;
            $address = $entity['headquartersAddress'] ?? $entity['legalAddress'] ?? [];
            $legal = $entity['legalAddress'] ?? [];
            $haystack = Str::lower(implode(' ', array_merge($legal['addressLines'] ?? [], [$legal['city'] ?? '', $address['city'] ?? ''], $address['addressLines'] ?? [])));
            if ($needles && !Str::contains($haystack, $needles)) continue;
            $region = $address['region'] ?? $legal['region'] ?? '';
            $stateCode = Str::after((string) $region, '-');
            $rows[] = [
                'name' => $this->display($name),
                'country' => $countryName,
                'city' => $city ? Str::title($city) : Str::title(Str::lower((string) ($address['city'] ?? $legal['city'] ?? ''))),
                'state' => strtoupper($countryCode) === 'IN' ? (self::INDIAN_STATES[$stateCode] ?? null) : ($stateCode ?: null),
                'registration' => $entity['registeredAs'] ?? null,
                'lei' => $record['id'] ?? null,
            ];
        }
        return ['rows' => $rows, 'last_page' => (int) $response->json('meta.pagination.lastPage', $page)];
    }

    /** "TITAN COMPANY LIMITED" -> "Titan Company Limited". */
    private function display(string $name): string
    {
        if (preg_match('/\p{Ll}/u', $name)) return $name;
        $title = Str::title(Str::lower($name));
        return preg_replace_callback('/\b(Llp|Llc|Plc|Gmbh|Pvt|Ltd|Bv|Nv|Ag|Sa|It|Ai|Hr|Ev|Usa|Uk|Uae|Fze|Fzco|Fzc|Dmcc)\b/', fn ($m) => match ($m[1]) {
            'Pvt' => 'Pvt', 'Ltd' => 'Ltd', 'Gmbh' => 'GmbH', default => strtoupper($m[1]),
        }, $title);
    }
}
