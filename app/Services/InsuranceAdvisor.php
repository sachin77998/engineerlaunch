<?php

namespace App\Services;

use App\Models\Insurer;
use Illuminate\Support\Collection;

/**
 * Insurance advisor: recommended cover, an estimated premium with its fee structure, and insurers ranked by the
 * public figures the owner has entered (claim settlement ratio, complaints, network hospitals).
 *
 * Fixed rules: motor third-party premiums are IRDAI's tariff; GST is 18% on motor insurance and nil on individual
 * health and life policies (exempt from 22 Sep 2025). Health and term premiums are estimates from typical market
 * rates for the stated profile: every insurer prices differently, so they are shown as a range, not a quote.
 */
class InsuranceAdvisor
{
    public const PRODUCTS = ['health' => 'Health insurance', 'term' => 'Term life insurance', 'car' => 'Car insurance', 'bike' => 'Bike insurance'];

    // Typical annual premium for ₹5 lakh individual health cover in a metro, by age of the eldest member.
    private const HEALTH_BASE = [25 => 6000, 30 => 7000, 35 => 8000, 40 => 9500, 45 => 11500, 50 => 15000, 55 => 19500, 60 => 25000, 65 => 33000, 70 => 42000, 200 => 52000];
    private const HEALTH_COVER = [500000 => 1.0, 1000000 => 1.3, 1500000 => 1.5, 2500000 => 1.75, 5000000 => 2.1, 10000000 => 2.5];
    private const CITY = ['metro' => [1.0, 'Metro city'], 'tier2' => [0.9, 'Tier-2 city'], 'tier3' => [0.82, 'Tier-3 city / town']];
    // Typical annual premium per ₹1 crore of term cover to age 60, non-smoking man, by age at entry.
    private const TERM_BASE = [18 => 8000, 25 => 9000, 30 => 11000, 35 => 14500, 40 => 20000, 45 => 29000, 50 => 42000, 55 => 62000, 60 => 90000, 65 => 130000];
    private const TERM_TILL = [60 => 1.0, 65 => 1.15, 70 => 1.35, 75 => 1.6, 85 => 2.0];
    // IRDAI third-party premiums (annual) by engine capacity.
    public const CAR_TP = ['1000' => ['Up to 1000 cc', 2094, 3.127], '1500' => ['1000–1500 cc', 3416, 3.283], 'above' => ['Above 1500 cc', 7897, 3.440]];
    public const BIKE_TP = ['75' => ['Up to 75 cc', 538, 1.676], '150' => ['75–150 cc', 714, 1.708], '350' => ['150–350 cc', 1366, 1.793], 'above' => ['Above 350 cc', 2804, 1.879]];

    public function advise(string $product, array $in): array
    {
        return match ($product) {
            'health' => $this->health($in),
            'term' => $this->term($in),
            'car', 'bike' => $this->motor($product, $in),
        } + ['insurers' => $this->shortlist($product)];
    }

    private function health(array $in): array
    {
        $adults = max(1, min(2, (int) ($in['adults'] ?? 1)));
        $children = max(0, min(4, (int) ($in['children'] ?? 0)));
        $age = max(18, min(90, (int) ($in['age'] ?? 30)));
        $city = self::CITY[$in['city'] ?? 'metro'] ?? self::CITY['metro'];
        $members = $adults + $children;
        // Recommended cover: hospital costs are higher in metros, and a family shares one floater sum.
        $levels = array_keys(self::HEALTH_COVER);
        $index = ($in['city'] ?? 'metro') === 'metro' ? ($members >= 3 ? 2 : 1) : ($members >= 3 ? 1 : 0);
        if ($age >= 45) $index++;
        $recommended = $levels[min($index, count($levels) - 1)];
        $cover = (int) ($in['cover'] ?? 0) ?: $recommended;
        $base = $this->band(self::HEALTH_BASE, $age);
        $family = 1 + 0.6 * ($adults - 1) + 0.25 * $children;
        $ped = !empty($in['ped']) ? 1.1 : 1.0;
        $premium = $base * $family * (self::HEALTH_COVER[$cover] ?? 1.0) * $city[0] * $ped;
        $plan = $members > 1 ? 'Family floater' : 'Individual';
        return [
            'title' => 'Health insurance', 'premium' => $premium, 'gst_rate' => 0.0, 'range' => [0.8, 1.25],
            'recommendation' => [
                ['Plan type', $plan . ' policy' . ($members > 1 ? " covering {$adults} " . ($adults > 1 ? 'adults' : 'adult') . ($children ? " and {$children} " . ($children > 1 ? 'children' : 'child') : '') : '')],
                ['Recommended cover', $this->lakh($recommended) . ($cover !== $recommended ? ' (you chose ' . $this->lakh($cover) . ')' : '')],
                ['Add a super top-up', '₹25–50 lakh super top-up over the base cover: large cover for a small extra premium'],
                ['Look for', 'No room-rent cap, no co-payment, restore/recharge benefit, short waiting period for pre-existing diseases (2–3 years), wide cashless network in ' . strtolower($city[1])],
                ...(!empty($in['ped']) ? [['Pre-existing illness', 'Declare it: waiting periods apply; plans with day-1 or 1-year PED cover cost more']] : []),
            ],
            'factors' => [['Eldest member age', $age], ['Members', $members], ['Sum insured', $this->lakh($cover)], ['City', $city[1]]],
        ];
    }

    private function term(array $in): array
    {
        $age = max(18, min(65, (int) ($in['age'] ?? 30)));
        $income = max(0, (float) ($in['income'] ?? 1000000));
        $existing = max(0, (float) ($in['existing'] ?? 0));
        $till = (int) ($in['till'] ?? 60);
        $till = array_key_exists($till, self::TERM_TILL) ? $till : 60;
        $multiple = $age < 30 ? 20 : ($age < 40 ? 15 : ($age < 50 ? 12 : 10));
        $need = max(2500000, ceil(max($income * $multiple - $existing, 0) / 2500000) * 2500000);
        $cover = (int) ($in['cover'] ?? 0) ?: (int) $need;
        $perCrore = $this->interpolate(self::TERM_BASE, $age) * self::TERM_TILL[$till];
        if (($in['gender'] ?? 'male') === 'female') $perCrore *= 0.85;
        if (!empty($in['smoker'])) $perCrore *= 1.6;
        $premium = $perCrore * $cover / 10000000 * ($cover >= 10000000 ? 0.95 : 1);
        return [
            'title' => 'Term life insurance', 'premium' => $premium, 'gst_rate' => 0.0, 'range' => [0.8, 1.25],
            'recommendation' => [
                ['Plan type', 'Pure term plan (no maturity benefit): the cheapest way to buy large life cover'],
                ['Recommended cover', $this->crore($need) . " ({$multiple}× income" . ($existing ? ' less existing cover' : '') . ')'],
                ['Cover till age', $till . ($till < 60 ? '' : ' (keep cover until your loans end and dependants are settled)')],
                ['Riders worth adding', 'Accidental death benefit, critical illness, waiver of premium on disability'],
                ['Look for', 'High claim settlement ratio (individual death claims) and a large number of claims settled; buy directly from the insurer\'s website to avoid commission'],
            ],
            'factors' => [['Age', $age], ['Gender', ucfirst($in['gender'] ?? 'male')], ['Smoker', !empty($in['smoker']) ? 'Yes' : 'No'], ['Cover', $this->crore($cover)], ['Cover till age', $till]],
        ];
    }

    private function motor(string $type, array $in): array
    {
        $table = $type === 'car' ? self::CAR_TP : self::BIKE_TP;
        $band = $table[$in['cc'] ?? array_key_first($table)] ?? reset($table);
        $price = max(10000, (float) ($in['price'] ?? ($type === 'car' ? 800000 : 100000)));
        $ageYears = max(0, (float) ($in['vehicle_age'] ?? 0));
        // IDV: ex-showroom price less IRDAI's depreciation by age.
        $dep = $ageYears <= 0.5 ? 0.05 : ($ageYears <= 1 ? 0.15 : ($ageYears <= 2 ? 0.2 : ($ageYears <= 3 ? 0.3 : ($ageYears <= 4 ? 0.4 : ($ageYears <= 5 ? 0.5 : 0.55)))));
        $idv = $price * (1 - $dep);
        $ncb = max(0, min(50, (float) ($in['ncb'] ?? 0)));
        $odTariff = $idv * $band[2] / 100 * ($ageYears > 5 ? 1.05 : 1) * (1 - $ncb / 100);
        $od = $odTariff * 0.55; // insurers price own-damage well below the old tariff
        $zeroDep = !empty($in['zero_dep']) && $ageYears <= 5 ? $od * 0.2 : 0;
        $pa = 375; // compulsory personal accident cover for the owner-driver (₹15 lakh)
        $premium = $band[1] + $od + $zeroDep + $pa;
        return [
            'title' => ucfirst($type) . ' insurance', 'premium' => $premium, 'gst_rate' => 18.0, 'range' => [0.85, 1.2],
            'breakdown' => [['Third-party premium (IRDAI tariff, ' . $band[0] . ')', $band[1]], ['Own-damage premium (after ' . (int) $ncb . '% NCB)', $od],
                ...($zeroDep ? [['Zero-depreciation add-on', $zeroDep]] : []), ['Personal accident cover, owner-driver', $pa]],
            'recommendation' => [
                ['Plan type', 'Comprehensive policy (third-party + own damage)' . ($ageYears <= 5 ? ' with zero-depreciation add-on' : '')],
                ['Insured declared value (IDV)', self::inr($idv) . ' (' . (int) ($dep * 100) . '% depreciation for a ' . $ageYears . '-year-old vehicle)'],
                ['Third-party only?', 'Legally enough, but pays nothing for damage to your own vehicle'],
                ['Look for', 'Wide cashless garage network, high claim settlement ratio, keep your no-claim bonus (NCB) when renewing'],
            ],
            'factors' => [['Engine', $band[0]], ['Ex-showroom price', self::inr($price)], ['Vehicle age', $ageYears . ' years'], ['No-claim bonus', (int) $ncb . '%']],
        ];
    }

    /** Insurers offering the product: ranked by claim settlement ratio, then fewest complaints, then network size. */
    public function shortlist(string $product): Collection
    {
        return Insurer::where('is_active', true)->get()->filter(fn (Insurer $i) => $i->offers($product))
            ->sortBy([fn ($a, $b) => ($b->claim_settlement_ratio ?? -1) <=> ($a->claim_settlement_ratio ?? -1),
                fn ($a, $b) => ($a->complaints_per_10k ?? PHP_INT_MAX) <=> ($b->complaints_per_10k ?? PHP_INT_MAX),
                fn ($a, $b) => ($b->network_hospitals ?? 0) <=> ($a->network_hospitals ?? 0), fn ($a, $b) => strcmp($a->name, $b->name)])->values();
    }

    /** ₹ with Indian digit grouping (₹7,65,000). */
    public static function inr(float $v): string
    {
        $n = (string) (int) round(abs($v));
        $grouped = strlen($n) > 3 ? preg_replace('/\B(?=(\d{2})+(?!\d))/', ',', substr($n, 0, -3)) . ',' . substr($n, -3) : $n;
        return ($v < 0 ? '−' : '') . '₹' . $grouped;
    }

    private function band(array $table, int $age): float
    {
        foreach ($table as $upTo => $value) if ($age <= $upTo) return (float) $value;
        return (float) end($table);
    }

    private function interpolate(array $table, int $age): float
    {
        $prev = null;
        foreach ($table as $at => $value) {
            if ($age <= $at) {
                if ($prev === null || $age === $at) return (float) $value;
                [$a0, $v0] = $prev;
                return $v0 + ($value - $v0) * ($age - $a0) / ($at - $a0);
            }
            $prev = [$at, $value];
        }
        return (float) end($table);
    }

    private function lakh(int|float $v): string
    {
        return $v >= 10000000 ? '₹' . rtrim(rtrim(number_format($v / 10000000, 2), '0'), '.') . ' crore' : '₹' . rtrim(rtrim(number_format($v / 100000, 1), '0'), '.') . ' lakh';
    }

    private function crore(int|float $v): string
    {
        return $this->lakh($v);
    }
}
