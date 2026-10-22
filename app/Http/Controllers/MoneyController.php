<?php

namespace App\Http\Controllers;

use App\Services\Money\FundAdvisor;
use App\Services\Money\FundModel;
use App\Services\Money\Profile;
use App\Services\Money\RetirementIncome;
use App\Services\Money\RetirementPlanner;
use App\Services\Money\TaxPlanner;
use Illuminate\Http\Request;

// Free money planner: retirement corpus, tax saving, retirement income & insurance, and fund picks with timing signals.
class MoneyController extends Controller
{
    public function index(RetirementPlanner $planner, RetirementIncome $income)
    {
        $p = Profile::load();
        $plan = $planner->plan($p);
        return view('money.index', ['p' => $p, 'plan' => $plan, 'protection' => $income->protection($p), 'instruments' => config('money.instruments'), 'relations' => Profile::RELATIONS]);
    }

    public function save(Request $r)
    {
        $r->validate(['age' => 'required|integer|min:18|max:90', 'retirement_age' => 'required|integer|min:30|max:80', 'monthly_salary' => 'required|numeric|min:0',
            'target_monthly_income' => 'required|numeric|min:0', 'investments' => 'nullable|array|max:15', 'family' => 'nullable|array|max:10']);
        $data = $r->except('_token');
        $data['investments'] = array_values($r->input('investments', []));
        $data['family'] = array_values($r->input('family', []));
        $data['target_in_todays_money'] = $r->boolean('target_in_todays_money');
        Profile::save($data);
        return redirect()->to(url()->previous() . '#result')->with('success', 'Plan updated.');
    }

    public function tax(Request $r, TaxPlanner $tax)
    {
        $p = Profile::load();
        $cgIn = [
            'asset' => $r->query('asset', 'equity'), 'gain' => (float) $r->query('gain', 5000000), 'holding_months' => (int) $r->query('holding_months', 120),
            'salary' => (float) $r->query('cg_salary', $p['age'] >= $p['retirement_age'] ? 0 : $p['monthly_salary'] * 12), 'other_income' => (float) $r->query('other_income', 0),
            'age' => (int) $r->query('cg_age', $p['age']), 'regime' => $r->query('regime', $p['regime']), 'years' => (int) $r->query('years', 3), 'family' => $p['family'],
        ];
        $salIn = ['monthly_salary' => (float) $r->query('monthly_salary', $p['monthly_salary']), 'age' => $p['age'], 'basic_pct' => (float) $r->query('basic_pct', 40),
            'd_80c' => (float) $r->query('d_80c', collect($p['investments'])->whereIn('type', ['elss', 'ppf', 'epf', 'endowment', 'ulip', 'term', 'ssy'])->sum('monthly') * 12),
            'd_nps' => (float) $r->query('d_nps', collect($p['investments'])->where('type', 'nps')->sum('monthly') * 12),
            'd_80d' => (float) $r->query('d_80d', collect($p['investments'])->where('type', 'health')->sum('monthly') * 12),
            'd_hra' => (float) $r->query('d_hra', 0), 'd_home_loan' => (float) $r->query('d_home_loan', 0), 'employer_nps' => (float) $r->query('employer_nps', 0)];
        return view('money.tax', ['p' => $p, 'cg' => $tax->capitalGains($cgIn), 'cgIn' => $cgIn, 'sal' => $tax->salary($salIn), 'salIn' => $salIn, 'relations' => Profile::RELATIONS]);
    }

    public function income(Request $r, RetirementPlanner $planner, RetirementIncome $income)
    {
        $p = Profile::load();
        $plan = $planner->plan($p);
        $corpus = (float) $r->query('corpus', $plan['corpus']);
        return view('money.income', ['p' => $p, 'plan' => $plan, 'corpus' => $corpus, 'inc' => $income->plan($corpus, $p, $r->filled('target') ? (float) $r->query('target') : $plan['target_monthly']), 'protection' => $income->protection($p)]);
    }

    public function funds(Request $r, FundAdvisor $advisor)
    {
        $p = Profile::load();
        $signal = $r->query('signal');
        $signals = \Illuminate\Support\Facades\DB::table('mf_model_scores as s')->join('mf_schemes as m', 'm.scheme_code', '=', 's.scheme_code')
            ->where('s.signal', '!=', 'pending')->when($signal && isset(FundAdvisor::SIGNALS[$signal]), fn ($q) => $q->where('s.signal', $signal))
            ->orderByDesc('s.score')->limit(60)->get(['m.id', 'm.scheme_code', 'm.name', 'm.amc', 'm.category', 'm.nav', 'm.return_1y', 'm.return_3y', 's.score', 's.signal', 's.reasons', 's.as_of']);
        $counts = \Illuminate\Support\Facades\DB::table('mf_model_scores')->where('signal', '!=', 'pending')->selectRaw('`signal`, count(*) c')->groupBy('signal')->pluck('c', 'signal');
        return view('money.funds', ['p' => $p, 'rec' => $advisor->recommend($p, $r->filled('monthly') ? (float) $r->query('monthly') : null), 'holdings' => $advisor->holdings($r->user()?->id),
            'signals' => $signals, 'counts' => $counts, 'signal' => $signal, 'model' => app(FundModel::class)->model()]);
    }
}
