<?php

namespace App\Services\Legal;

use App\Models\Legal\LegalService;

// Estimated stamp duty and registration fee for a rent agreement, plus the service fees. Mirrored by the calculator's JavaScript.
class RentAgreementCost
{
    public static function state(string $city): ?string
    {
        return config('rent_agreement.cities')[$city] ?? null;
    }

    /** Stamp duty estimate, or null when this state has no rule configured. */
    public static function stamp(?string $state, int $rent, int $deposit, int $months): ?int
    {
        $rule = config('rent_agreement.states')[$state]['stamp'] ?? null;
        if (!$rule) return null;
        $bracket = collect($rule)->first(fn ($b) => $b['upto'] === null || $months <= $b['upto']) ?? end($rule);
        $amount = isset($bracket['fixed']) ? $bracket['fixed'] : self::base($bracket['base'], $rent, $deposit, $months) * $bracket['pct'] / 100;
        return (int) max(config('rent_agreement.min_stamp'), ceil($amount));
    }

    public static function registration(?string $state, int $rent, int $deposit, int $months): ?int
    {
        $rule = config('rent_agreement.states')[$state]['registration'] ?? null;
        if (!$rule) return null;
        return (int) ceil($rule['fixed'] ?? self::base($rule['base'], $rent, $deposit, $months) * $rule['pct'] / 100);
    }

    public static function registrationRequired(?string $state, int $months): bool
    {
        return $months > 11 || (config('rent_agreement.states')[$state]['registration_required'] ?? false);
    }

    private static function base(string $base, int $rent, int $deposit, int $months): float
    {
        return match ($base) {
            'annual_rent' => $rent * 12,
            'annual_rent_plus_deposit' => $rent * 12 + $deposit,
            'total_rent_plus_deposit' => $rent * $months + $deposit,
            'consideration' => $rent * $months + $deposit * 0.10 * $months / 12,
            default => 0,
        };
    }

    /** Full breakdown for an agreement: service lines (priced or on call) and government estimates. */
    public static function breakdown(string $city, int $rent, int $deposit, int $months, string $type, array $addons): array
    {
        $state = self::state($city);
        $slugs = array_values(array_unique(array_filter([config('rent_agreement.agreement_service'), $type === 'registered' ? config('rent_agreement.registration_service') : null, ...$addons])));
        $services = LegalService::whereIn('slug', $slugs)->where('is_active', true)->get()->sortBy(fn ($s) => array_search($s->slug, $slugs, true))->values();
        $stamp = self::stamp($state, $rent, $deposit, $months);
        $reg = $type === 'registered' ? self::registration($state, $rent, $deposit, $months) : null;
        return [
            'state' => $state, 'services' => $services, 'service_total' => (int) $services->sum('price'), 'on_call' => $services->contains(fn ($s) => !$s->price),
            'stamp' => $stamp, 'registration' => $reg, 'total' => (int) $services->sum('price') + (int) $stamp + (int) $reg,
        ];
    }
}
