<?php

namespace App\Http\Controllers;

use App\Services\Money\MoneyRules;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

// Owner editor for the money planner rule tables (tax slabs, surcharges, capital gains, deductions, instruments,
// income products, plan examples) and settings (tax year, cess, standard deduction, rebate, defaults).
class AdminMoneyRulesController extends Controller
{
    /** table => [title, columns]; column => [type, label, options?]. Types: text, num, int, select, bool, csv. */
    public const TABLES = [
        'money_tax_slabs' => ['Income-tax slabs (upto blank = no upper limit)', [
            'regime' => ['select', 'Regime', ['new', 'old', 'old_senior', 'old_super_senior']], 'upto' => ['num?', 'Income up to (₹)'], 'rate' => ['num', 'Rate %'], 'sort_order' => ['int', 'Order']]],
        'money_tax_surcharges' => ['Surcharge', ['regime' => ['select', 'Regime', ['new', 'old']], 'above' => ['num', 'Income above (₹)'], 'rate' => ['num', 'Rate %']]],
        'money_capital_gains_rules' => ['Capital gains (rate blank = slab rate)', [
            'asset' => ['text', 'Asset'], 'ltcg_months' => ['int?', 'Long-term after (months)'], 'stcg_rate' => ['num?', 'STCG %'], 'ltcg_rate' => ['num?', 'LTCG %'],
            'ltcg_exemption' => ['num', 'LTCG exemption (₹)'], 'section_st' => ['text?', 'Section (ST)'], 'section_lt' => ['text?', 'Section (LT)']]],
        'money_deductions' => ['Deductions', [
            'section' => ['text', 'Section'], 'label' => ['text', 'What qualifies'], 'limit' => ['num?', 'Limit (₹)'], 'limit_senior' => ['num?', 'Senior limit'],
            'parents' => ['num?', 'Parents'], 'parents_senior' => ['num?', 'Senior parents'], 'limit_pct_basic' => ['num?', '% of basic'], 'regimes' => ['csv', 'Regimes (old,new)'],
            'sort_order' => ['int', 'Order'], 'is_active' => ['bool', 'On']]],
        'money_instruments' => ['Investment instruments', [
            'key' => ['text', 'Key'], 'label' => ['text', 'Name'], 'expected_return' => ['num', 'Expected return %'],
            'tax_class' => ['select', 'Tax treatment', ['equity', 'debt', 'fd', 'exempt', 'nps', 'gold', 'none']], 'liquid' => ['bool', 'Liquid'], 'sort_order' => ['int', 'Order'], 'is_active' => ['bool', 'On']]],
        'money_income_products' => ['Retirement income products', [
            'key' => ['text', 'Key'], 'label' => ['text', 'Name'], 'rate' => ['num', 'Rate %'], 'withdrawal' => ['num?', 'Withdrawal %'], 'max_per_person' => ['num?', 'Max per person (₹)'],
            'max_joint' => ['num?', 'Max joint (₹)'], 'min_age' => ['int?', 'Min age'], 'payout' => ['select', 'Payout', ['monthly', 'quarterly', 'half-yearly', 'yearly']],
            'tax_note' => ['text?', 'Tax'], 'risk' => ['text?', 'Risk'], 'sort_order' => ['int', 'Order'], 'is_active' => ['bool', 'On']]],
        'money_plan_examples' => ['Plan examples', ['category' => ['select', 'Category', ['immediate_annuity', 'deferred_annuity', 'nps', 'term', 'health']], 'name' => ['text', 'Plan'], 'sort_order' => ['int', 'Order']]],
    ];
    private const SETTINGS = ['tax_year' => 'text', 'cess' => 'num', 'capital_gains_surcharge_cap' => 'num', '54EC_cap' => 'num', 'new_standard_deduction' => 'num', 'new_rebate_limit' => 'num',
        'new_rebate_max' => 'num', 'old_standard_deduction' => 'num', 'old_rebate_limit' => 'num', 'old_rebate_max' => 'num', 'gift_exempt_relatives' => 'csv'];

    public function index()
    {
        (new MoneyRules)->build();   // seeds on first visit
        return view('admin.money-rules', [
            'tables' => collect(self::TABLES)->map(fn ($t, $name) => ['title' => $t[0], 'cols' => $t[1], 'rows' => DB::table($name)->orderBy(isset($t[1]['sort_order']) ? 'sort_order' : 'id')->get()]),
            'settings' => DB::table('money_settings')->pluck('value', 'key')->map(fn ($v) => json_decode($v, true)), 'settingKeys' => self::SETTINGS,
            'instruments' => DB::table('money_instruments')->orderBy('sort_order')->pluck('label', 'key'),
        ]);
    }

    public function table(Request $r, string $table)
    {
        abort_unless(isset(self::TABLES[$table]), 404);
        $cols = self::TABLES[$table][1];
        $errors = []; $writes = [];
        foreach ((array) $r->input('rows', []) as $key => $row) {
            $isNew = str_starts_with((string) $key, 'new');
            if (!empty($row['_delete'])) { if (!$isNew) $writes[] = ['delete', (int) $key]; continue; }
            if ($isNew && collect($row)->except('_delete')->filter(fn ($v) => $v !== null && $v !== '')->isEmpty()) continue;
            $data = [];
            foreach ($cols as $c => [$type, $label]) {
                $v = $row[$c] ?? null;
                $optional = str_ends_with($type, '?'); $type = rtrim($type, '?');
                if ($type === 'bool') { $data[$c] = !empty($v); continue; }
                if ($v === null || $v === '') { if ($optional) { $data[$c] = null; continue; } $errors[] = "$label is required."; continue 2; }
                if (in_array($type, ['num', 'int'], true) && !is_numeric($v)) { $errors[] = "$label must be a number."; continue 2; }
                $data[$c] = match ($type) {
                    'num' => (float) $v, 'int' => (int) $v,
                    'csv' => json_encode(array_values(array_filter(array_map('trim', explode(',', $v))))),
                    'select' => in_array($v, $cols[$c][2], true) ? $v : $cols[$c][2][0],
                    default => mb_substr(trim($v), 0, 300),
                };
            }
            $writes[] = $isNew ? ['insert', $data] : ['update', (int) $key, $data];
        }
        if ($errors) return back()->withErrors(array_unique($errors))->withInput();
        try {
            DB::transaction(function () use ($table, $writes) {
                foreach ($writes as $w) match ($w[0]) {
                    'delete' => DB::table($table)->where('id', $w[1])->delete(),
                    'insert' => DB::table($table)->insert($w[1] + ['created_at' => now(), 'updated_at' => now()]),
                    'update' => DB::table($table)->where('id', $w[1])->update($w[2] + ['updated_at' => now()]),
                };
                // Invalid slab structure rolls the whole save back.
                if ($table === 'money_tax_slabs' && ($bad = $this->slabProblem())) throw new \InvalidArgumentException($bad);
            });
        } catch (\InvalidArgumentException $e) {
            return back()->withErrors([$e->getMessage()])->withInput();
        }
        MoneyRules::flush();
        return back()->with('status', self::TABLES[$table][0] . ' saved.');
    }

    public function settings(Request $r)
    {
        $put = fn ($k, $v) => DB::table('money_settings')->updateOrInsert(['key' => $k], ['value' => json_encode($v, JSON_UNESCAPED_UNICODE), 'updated_at' => now(), 'created_at' => now()]);
        foreach (self::SETTINGS as $k => $type) {
            $v = $r->input("s.$k");
            if ($type === 'num' && !is_numeric($v)) return back()->withErrors(["$k must be a number."])->withInput();
            $put($k, match ($type) { 'num' => (float) $v, 'csv' => array_values(array_filter(array_map('trim', explode(',', (string) $v)))), default => trim((string) $v) });
        }
        $d = DB::table('money_settings')->where('key', 'defaults')->value('value');
        $defaults = $d ? json_decode($d, true) : [];
        foreach ((array) $r->input('d', []) as $k => $v) if (array_key_exists($k, $defaults) && !is_array($defaults[$k]))
            $defaults[$k] = is_bool($defaults[$k]) ? (bool) $v : (is_numeric($defaults[$k]) ? (float) $v : (string) $v);
        $defaults['investments'] = collect((array) $r->input('inv', []))->filter(fn ($i) => !empty($i['type']) && is_numeric($i['monthly'] ?? null) && (float) $i['monthly'] > 0)
            ->map(fn ($i) => ['type' => $i['type'], 'monthly' => (float) $i['monthly']])->values()->all();
        $put('defaults', $defaults);
        MoneyRules::flush();
        return back()->with('status', 'Settings and defaults saved.');
    }

    public function reset()
    {
        (new MoneyRules)->seed(null, true);
        return back()->with('status', 'Money rules restored from config/money.php.');
    }

    /** Each regime's slabs must rise and end with one open-ended slab. */
    private function slabProblem(): ?string
    {
        foreach (DB::table('money_tax_slabs')->orderBy('sort_order')->get()->groupBy('regime') as $regime => $rows) {
            $limits = $rows->pluck('upto');
            if ($limits->filter(fn ($v) => $v === null)->count() !== 1 || $limits->last() !== null) return "Slabs for '$regime' need exactly one open-ended (blank) slab, placed last.";
            $vals = $limits->filter()->map(fn ($v) => (float) $v)->values();
            if ($vals->sort()->values()->all() !== $vals->all()) return "Slabs for '$regime' must be in rising order.";
        }
        return null;
    }
}
