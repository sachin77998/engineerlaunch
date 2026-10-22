<?php

namespace App\Services\Money;

use Illuminate\Support\Facades\DB;

// The person's financial profile, shared by every planner page and the chatbot.
// Signed-in users keep it in money_profiles; guests keep it in their session.
class Profile
{
    public const RELATIONS = ['spouse' => 'Spouse', 'adult_child' => 'Adult child (18+)', 'minor_child' => 'Minor child', 'parent' => 'Parent', 'sibling' => 'Brother / sister'];

    public static function load(): array
    {
        $saved = null;
        if ($id = auth()->id()) $saved = rescue(fn () => DB::table('money_profiles')->where('user_id', $id)->value('data'), null, false);
        $data = $saved ? json_decode($saved, true) : session('money_profile');
        return self::normalize(is_array($data) ? $data : []);
    }

    public static function save(array $data): array
    {
        $p = self::normalize($data);
        session(['money_profile' => $p]);
        if ($id = auth()->id()) rescue(fn () => DB::table('money_profiles')->updateOrInsert(['user_id' => $id], ['data' => json_encode($p), 'updated_at' => now(), 'created_at' => now()]), null, false);
        return $p;
    }

    public static function normalize(array $in): array
    {
        $d = config('money.defaults');
        $num = fn ($k, $min = 0, $max = 1e12) => max($min, min($max, (float) (is_numeric($in[$k] ?? null) ? $in[$k] : $d[$k])));
        $p = [
            'age' => (int) $num('age', 18, 90), 'retirement_age' => (int) $num('retirement_age', 30, 80), 'life_expectancy' => (int) $num('life_expectancy', 60, 105),
            'monthly_salary' => $num('monthly_salary'), 'salary_growth' => $num('salary_growth', 0, 30), 'monthly_expenses' => $num('monthly_expenses'),
            'current_savings' => $num('current_savings'), 'target_monthly_income' => $num('target_monthly_income'), 'inflation' => $num('inflation', 0, 15),
            'post_retirement_return' => $num('post_retirement_return', 0, 15), 'step_up' => $num('step_up', 0, 30),
            'existing_life_cover' => $num('existing_life_cover'), 'existing_health_cover' => $num('existing_health_cover'), 'liabilities' => $num('liabilities'),
            'risk' => in_array($in['risk'] ?? null, ['conservative', 'moderate', 'aggressive'], true) ? $in['risk'] : $d['risk'],
            'regime' => in_array($in['regime'] ?? null, ['new', 'old'], true) ? $in['regime'] : $d['regime'],
            'city_tier' => in_array($in['city_tier'] ?? null, ['metro', 'non_metro'], true) ? $in['city_tier'] : $d['city_tier'],
            'target_in_todays_money' => filter_var($in['target_in_todays_money'] ?? $d['target_in_todays_money'], FILTER_VALIDATE_BOOLEAN),
        ];
        if ($p['retirement_age'] <= $p['age']) $p['retirement_age'] = min(80, $p['age'] + 1);
        if ($p['life_expectancy'] <= $p['retirement_age']) $p['life_expectancy'] = $p['retirement_age'] + 1;
        $types = array_keys(config('money.instruments'));
        $rows = array_key_exists('investments', $in) && is_array($in['investments']) ? $in['investments'] : $d['investments'];
        $p['investments'] = collect($rows)->filter(fn ($r) => is_array($r) && in_array($r['type'] ?? null, $types, true) && is_numeric($r['monthly'] ?? null) && $r['monthly'] > 0)
            ->map(fn ($r) => ['type' => $r['type'], 'monthly' => (float) $r['monthly'],
                'return' => is_numeric($r['return'] ?? null) ? max(0, min(30, (float) $r['return'])) : null,
                'step_up' => is_numeric($r['step_up'] ?? null) ? max(0, min(30, (float) $r['step_up'])) : null])->take(15)->values()->all();
        $rels = array_keys(self::RELATIONS);
        $p['family'] = collect(is_array($in['family'] ?? null) ? $in['family'] : $d['family'])->filter(fn ($m) => is_array($m) && in_array($m['relation'] ?? null, $rels, true))
            ->map(fn ($m) => ['relation' => $m['relation'], 'name' => mb_substr(trim((string) ($m['name'] ?? '')), 0, 60), 'age' => (int) ($m['age'] ?? 0), 'income' => max(0, (float) ($m['income'] ?? 0))])->take(10)->values()->all();
        return $p;
    }
}
