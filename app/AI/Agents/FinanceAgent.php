<?php

namespace App\AI\Agents;

use App\AI\DTO\AgentRequest;
use App\AI\DTO\AgentResponse;
use App\AI\Services\AiGateway;
use App\Services\Money\Format;
use App\Services\Money\FundAdvisor;
use App\Services\Money\Profile;
use App\Services\Money\RetirementIncome;
use App\Services\Money\RetirementPlanner;
use App\Services\Money\TaxPlanner;

// Money questions: the numbers come from the planners (never from the model); the AI only explains them.
class FinanceAgent
{
    public const KEYWORDS = ['tax', 'invest', 'sip', 'mutual fund', 'fund', 'retire', 'pension', 'insurance', 'policy', 'lic', 'fd', 'fixed deposit', 'rd', 'recurring', 'ppf', 'nps', 'elss',
        'salary', 'save', 'saving', 'corpus', 'lakh', 'crore', 'monthly income', 'annuity', 'scss', '80c', 'capital gain', 'ltcg', 'stcg', 'emi', 'loan', 'interest', 'return', 'wealth', 'money', 'buy', 'exit', 'redeem'];

    public function __construct(private AiGateway $gateway, private RetirementPlanner $planner, private TaxPlanner $tax, private RetirementIncome $income, private FundAdvisor $funds) {}

    public function handle(AgentRequest $request): AgentResponse
    {
        $msg = mb_strtolower($request->message);
        $p = array_replace(Profile::load(), $this->extract($msg));
        $p = Profile::normalize($p);
        $facts = ['profile_used' => array_intersect_key($p, array_flip(['age', 'retirement_age', 'monthly_salary', 'target_monthly_income', 'risk', 'regime', 'investments']))];
        $links = [];
        if ($this->mentions($msg, ['tax', 'capital gain', 'ltcg', 'stcg', 'section', 'wife', 'spouse', 'children', 'son', 'daughter', 'gift', 'huf', '80c'])) {
            $gain = $this->amountNear($msg, ['earned', 'gain', 'profit', 'made', 'corpus']) ?? 5000000;
            $family = $p['family'];
            if (!$family && $this->mentions($msg, ['wife', 'spouse', 'husband'])) $family[] = ['relation' => 'spouse', 'income' => 0];
            if (!$family && $this->mentions($msg, ['children', 'son', 'daughter', 'kids'])) { $family[] = ['relation' => 'adult_child', 'income' => 0, 'age' => 25]; $family[] = ['relation' => 'adult_child', 'income' => 0, 'age' => 22]; }
            $years = preg_match('/(\d{1,2})\s*(?:years?|yrs?)/', $msg, $m) ? (int) $m[1] : 10;
            $cg = $this->tax->capitalGains(['asset' => str_contains($msg, 'property') || str_contains($msg, 'house') || str_contains($msg, 'land') ? 'property' : (str_contains($msg, 'fd') ? 'fd' : 'equity'),
                'gain' => $gain, 'holding_months' => max(12, $years * 12), 'salary' => 0, 'other_income' => 0, 'age' => max(60, $p['age']), 'years' => 3, 'family' => $family]);
            $facts['capital_gains_plan'] = ['gain' => $cg['gain'], 'tax_if_sold_at_once' => $cg['base_tax'], 'section' => $cg['section'],
                'options' => array_map(fn ($o) => array_intersect_key($o, array_flip(['title', 'tax', 'saved', 'section', 'how'])), $cg['options'])];
            $facts['salary_tax'] = array_intersect_key($this->tax->salary(['monthly_salary' => $p['monthly_salary'], 'age' => $p['age']]), array_flip(['better', 'difference', 'room', 'monthly_take_home_new']));
            $links[] = ['Tax saver', route('money.tax')];
        }
        $taxQuestion = isset($facts['capital_gains_plan']);
        $retireWords = $taxQuestion ? ['retire', 'pension', 'corpus', 'monthly income'] : ['retire', 'pension', 'corpus', 'monthly income', 'salary', 'save', 'saving', 'sip', 'fd', 'rd', 'policy', 'invest', 'lakh', 'month'];
        if ($this->mentions($msg, $retireWords)) {
            $plan = $this->planner->plan($p);
            $facts['retirement_plan'] = array_intersect_key($plan, array_flip(['years', 'corpus', 'corpus_today', 'invested', 'income', 'income_today', 'target_monthly', 'target_today', 'required', 'gap', 'extra_sip', 'on_track', 'savings_rate', 'allocation', 'switch', 'safety']))
                + ['by_investment' => array_map(fn ($r) => array_intersect_key($r, array_flip(['label', 'monthly', 'return', 'corpus'])), $plan['rows'])];
            $links[] = ['Retirement plan', route('money.index')];
        }
        if ($this->mentions($msg, ['insurance', 'policy', 'lic', 'annuity', 'pension', 'term', 'health', 'beema', 'bima'])) {
            $plan ??= $this->planner->plan($p);
            $inc = $this->income->plan($plan['corpus'], $p, $plan['target_monthly']);
            $facts['retirement_income_plan'] = ['monthly' => $inc['monthly'], 'monthly_after_tax' => $inc['monthly_after_tax'], 'buckets' => array_map(fn ($b) => array_intersect_key($b, array_flip(['label', 'amount', 'monthly', 'rate'])), $inc['buckets']), 'example_plans' => $inc['plans']];
            $facts['protection'] = $this->income->protection($p);
            $links[] = ['Retirement income & insurance', route('money.income')];
        }
        if ($this->mentions($msg, ['fund', 'mutual', 'sip', 'buy', 'exit', 'redeem', 'which mf', 'recommend'])) {
            $rec = $this->funds->recommend($p);
            $facts['fund_mix'] = ['allocation' => $rec['mix'], 'slots' => array_map(fn ($s) => ['category' => $s['category'], 'monthly' => $s['monthly'],
                'funds' => collect($s['funds'])->map(fn ($f) => ['name' => $f->name, 'return_3y' => $f->return_3y, 'model_score' => $f->score, 'signal' => FundAdvisor::SIGNALS[$f->signal ?? 'pending'] ?? null])->all()], $rec['slots'])];
            $links[] = ['Fund picks & signals', route('money.funds')];
        }
        $context = $request->context + ['computed' => $facts, 'tax_year' => config('money.tax_year')];
        $ai = $this->gateway->generate(new AgentRequest($request->message, $request->userId, 'finance', $context, $request->metadata), $this->instructions());
        $text = $ai->success ? $ai->message : $this->fallback($facts);
        return AgentResponse::success($text, 'finance', ['links' => $links], [], ['ai' => $ai->success]);
    }

    private function instructions(): string
    {
        return "You are the money assistant of an Indian career and investing portal. Answer in the user's language (English or Hinglish), briefly, with ₹ amounts in Indian format (lakh/crore).\n"
            . "Use ONLY the numbers in context.computed — never invent figures, fund names, rates or sections. Mention the income-tax section for every tax-saving idea.\n"
            . "Be correct about clubbing: gifts to a spouse or minor child do not save tax (s.64); gifts to adult children/parents do (s.56(2)(x)).\n"
            . "Prefer term insurance plus investing over endowment/ULIP policies when the computed switch figures show it. End with one line: 'This is educational guidance, not personalised investment advice.'";
    }

    // Plain answer built from the computed facts when the AI provider is unavailable.
    private function fallback(array $f): string
    {
        $out = [];
        // Lead with what was asked: tax first, then insurance/pension, then the retirement projection.
        if ($c = $f['capital_gains_plan'] ?? null) {
            $out[] = 'Tax on a gain of ' . Format::short($c['gain']) . ' if sold at once: ₹' . Format::inr($c['tax_if_sold_at_once']) . " (section {$c['section']}). Ways to save:";
            foreach (array_slice($c['options'], 0, 6) as $o) $out[] = "• {$o['title']} ({$o['section']}): tax ₹" . Format::inr($o['tax']) . ($o['saved'] > 0 ? ', saves ₹' . Format::inr($o['saved']) : ', no saving') . '.';
            unset($f['capital_gains_plan']);
        }
        if ($i = $f['retirement_income_plan'] ?? null) {
            $out[] = 'Monthly income plan for your corpus (≈ ₹' . Format::inr($i['monthly']) . '/month, ₹' . Format::inr($i['monthly_after_tax']) . ' after tax): ' . collect($i['buckets'])->map(fn ($b) => $b['label'] . ' ₹' . Format::inr($b['monthly']) . '/month')->implode('; ') . '.';
            $out[] = 'Pension plans to compare — immediate annuity: ' . implode(', ', $i['example_plans']['immediate_annuity']) . '; deferred: ' . implode(', ', $i['example_plans']['deferred_annuity']) . '.';
            unset($f['retirement_income_plan']);
        }
        if ($r = $f['retirement_plan'] ?? null) {
            $out[] = "At retirement (in {$r['years']} years) your investments can grow to about " . Format::short($r['corpus']) . ' (' . Format::short($r['corpus_today']) . " in today's money), enough for about ₹" . Format::inr($r['income']) . ' a month.';
            $out[] = $r['on_track'] ? 'That covers your target of ₹' . Format::inr($r['target_monthly']) . ' a month.' : 'To get ₹' . Format::inr($r['target_monthly']) . ' a month you need ' . Format::short($r['required']) . '; add about ₹' . Format::inr($r['extra_sip']) . ' a month to an equity SIP to close the gap.';
            foreach (array_slice($r['switch'], 0, 2) as $s) $out[] = "{$s['label']}: ₹" . Format::inr($s['monthly']) . '/month becomes ' . Format::short($s['now']) . '; in an equity fund it could be ' . Format::short($s['in_equity']) . '.';
        }
        if ($c = $f['capital_gains_plan'] ?? null) {
            $out[] = 'Tax on a gain of ' . Format::short($c['gain']) . ' if sold at once: ₹' . Format::inr($c['tax_if_sold_at_once']) . " (section {$c['section']}). Ways to save:";
            foreach (array_slice($c['options'], 0, 5) as $o) $out[] = "• {$o['title']} ({$o['section']}): tax ₹" . Format::inr($o['tax']) . ($o['saved'] > 0 ? ', saves ₹' . Format::inr($o['saved']) : ', no saving') . '.';
        }
        if ($pr = $f['protection'] ?? null) $out[] = 'Insurance: term cover of ' . Format::short($pr['term_cover']) . ' (about ₹' . Format::inr($pr['term_premium_month']) . '/month) and health cover of ' . Format::short($pr['health_cover']) . '.';
        if ($i = $f['retirement_income_plan'] ?? null) $out[] = 'Retirement income mix: ' . collect($i['buckets'])->map(fn ($b) => $b['label'] . ' ₹' . Format::inr($b['monthly']) . '/month')->implode('; ') . '.';
        if ($m = $f['fund_mix'] ?? null) $out[] = 'Suggested fund mix: ' . collect($m['slots'])->map(fn ($s) => $s['category'] . ' ₹' . Format::inr($s['monthly']))->implode(', ') . '.';
        if (!$out) $out[] = 'Tell me your monthly salary, age and how much you invest each month (e.g. "salary 39000, age 30, 5000 in mutual fund, 2000 in policy, retire at 60, want 1.5 lakh a month") and I will work out your retirement income and tax savings.';
        return implode("\n", $out) . "\n\nThis is educational guidance, not personalised investment advice.";
    }

    /** Pulls salary, age, retirement age, target income and per-product monthly amounts out of a message. */
    public function extract(string $m): array
    {
        $out = [];
        if (preg_match('/(?:salary|income|earn(?:ing)?)\D{0,15}([\d.,]+\s*(?:k|lakh|lac|l)?)/', $m, $x) || preg_match('/([\d.,]+\s*(?:k|lakh|lac)?)\s*(?:salary|per month salary)/', $m, $x)) $out['monthly_salary'] = $this->num($x[1]);
        if (preg_match('/(?:age|aged|i am|i\'m)\s*(?:is\s*)?(\d{2})\b/', $m, $x) || preg_match('/(\d{2})\s*(?:years? old|yrs? old)/', $m, $x)) $out['age'] = (int) $x[1];
        if (preg_match('/retire(?:ment)?\s*(?:at|age|by)?\s*(?:age\s*)?(\d{2})/', $m, $x)) $out['retirement_age'] = (int) $x[1];
        if (preg_match('/([\d.,]+\s*(?:k|lakh|lac|l|crore|cr)?)\s*(?:per month|a month|monthly|\/month|pm)\s*(?:income|pension|coming)?/', $m, $x) && preg_match('/(retire|pension|income|coming|want|need)/', $m)) {
            $v = $this->num($x[1]); if ($v >= 10000) $out['target_monthly_income'] = $v;
        }
        $map = ['equity_mf' => 'mutual fund|mf|sip|equity', 'endowment' => 'policy|lic|endowment|money back', 'fd' => '\bfd\b|fixed deposit', 'rd' => '\brd\b|recurring', 'ulip' => 'ulip|insurance policy',
            'ppf' => 'ppf', 'nps' => 'nps', 'term' => 'term', 'health' => 'health insurance|mediclaim', 'gold' => 'gold', 'elss' => 'elss'];
        $inv = [];
        foreach ($map as $type => $words) {
            if (preg_match_all('/([\d.,]+\s*(?:k)?)\s*(?:rs|₹|rupees)?\s*(?:monthly\s*)?(?:in|into|for|on|to)\s*(?:an?\s*|the\s*)?(?:' . $words . ')/', $m, $all)) {
                foreach ($all[1] as $amt) if (($v = $this->num($amt)) > 0) $inv[] = ['type' => $type, 'monthly' => $v];
            }
        }
        // "insurance policy" also matches "policy"; keep the most specific match per amount.
        if ($inv) $out['investments'] = collect($inv)->reverse()->unique(fn ($i) => $i['monthly'] . (in_array($i['type'], ['ulip', 'endowment'], true) ? 'pol' : $i['type']))->reverse()->values()->all();
        return $out;
    }

    private function amountNear(string $m, array $words): ?float
    {
        foreach ($words as $w) if (preg_match('/' . $w . '\D{0,20}([\d.,]+\s*(?:k|lakh|lac|l|crore|cr))/', $m, $x) || preg_match('/([\d.,]+\s*(?:lakh|lac|crore|cr))\D{0,20}' . $w . '/', $m, $x)) return $this->num($x[1]);
        return preg_match('/([\d.,]+\s*(?:lakh|lac|crore|cr))/', $m, $x) ? $this->num($x[1]) : null;
    }

    private function num(string $s): float
    {
        $s = trim(str_replace(',', '', $s));
        $v = (float) $s;
        if (preg_match('/(crore|cr)$/', $s)) $v *= 1e7; elseif (preg_match('/(lakh|lac|l)$/', $s)) $v *= 1e5; elseif (str_ends_with($s, 'k')) $v *= 1e3;
        return $v;
    }

    private function mentions(string $m, array $words): bool
    {
        foreach ($words as $w) if (preg_match('/\b' . preg_quote($w, '/') . '/', $m)) return true;
        return false;
    }
}
