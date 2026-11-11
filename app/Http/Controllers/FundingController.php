<?php

namespace App\Http\Controllers;

use App\Models\FundingRound;
use App\Models\Investor;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

// Funding rounds and investors read from funding news (see FundingRoundImporter).
class FundingController extends Controller
{
    public const PERIODS = ['30' => 'Last 30 days', '90' => 'Last 90 days', '365' => 'Last 12 months', 'all' => 'All time'];

    public function rounds(Request $request)
    {
        $f = $request->validate(['q' => 'nullable|string|max:100', 'type' => 'nullable|in:' . implode(',', array_keys(FundingRound::TYPES)), 'period' => 'nullable|in:' . implode(',', array_keys(self::PERIODS))]);
        $period = $f['period'] ?? '90';
        $rounds = FundingRound::query()
            ->with(['company:id,name,slug,logo_url,industry', 'investors:id,name,slug'])
            ->when($f['q'] ?? null, fn ($q, $v) => $q->where(fn ($w) => $w->where('company_name', 'like', '%' . $v . '%')
                ->orWhereHas('investors', fn ($i) => $i->where('name', 'like', '%' . $v . '%'))))
            ->when($f['type'] ?? null, fn ($q, $v) => $q->where('round_type', $v))
            ->when($period !== 'all', fn ($q) => $q->where('announced_on', '>=', now()->subDays((int) $period)->toDateString()))
            ->orderByDesc('announced_on')->orderByDesc('amount_usd')
            ->paginate(30)->withQueryString();
        $stats = Cache::remember('funding:stats', now()->addMinutes(30), fn () => [
            'rounds_30' => FundingRound::where('announced_on', '>=', now()->subDays(30)->toDateString())->count(),
            'usd_30' => (float) FundingRound::where('announced_on', '>=', now()->subDays(30)->toDateString())->sum('amount_usd'),
            'investors_90' => DB::table('funding_round_investor')->join('funding_rounds', 'funding_rounds.id', '=', 'funding_round_investor.funding_round_id')
                ->where('announced_on', '>=', now()->subDays(90)->toDateString())->distinct()->count('investor_id'),
        ]);
        return view('funding.rounds', ['rounds' => $rounds, 'f' => $f + ['period' => $period], 'stats' => $stats]);
    }

    public function investors(Request $request)
    {
        $f = $request->validate(['q' => 'nullable|string|max:100', 'type' => 'nullable|in:' . implode(',', array_keys(Investor::TYPES)), 'sort' => 'nullable|in:rounds,leads,recent']);
        $investors = Investor::query()->where('rounds_count', '>', 0)
            ->when($f['q'] ?? null, fn ($q, $v) => $q->where('name', 'like', '%' . $v . '%'))
            ->when($f['type'] ?? null, fn ($q, $v) => $q->where('type', $v))
            ->orderByDesc(['rounds' => 'rounds_count', 'leads' => 'lead_count', 'recent' => 'last_invested_on'][$f['sort'] ?? 'rounds'])->orderBy('name')
            ->paginate(40)->withQueryString();
        // The three most recent portfolio companies of each investor on the page.
        $recent = DB::table('funding_round_investor as fri')->join('funding_rounds as r', 'r.id', '=', 'fri.funding_round_id')
            ->whereIn('fri.investor_id', $investors->pluck('id'))->orderByDesc('r.announced_on')
            ->get(['fri.investor_id', 'r.company_name'])->groupBy('investor_id')->map(fn ($g) => $g->pluck('company_name')->unique()->take(3)->values());
        return view('funding.investors', ['investors' => $investors, 'f' => $f, 'recent' => $recent]);
    }

    public function investor(Investor $investor)
    {
        $rounds = $investor->rounds()->with(['company:id,name,slug,logo_url,industry', 'investors:id,name,slug'])->get();
        // Co-investors: other investors in the same rounds (the investor <-> round bipartite graph, one hop).
        $coInvestors = DB::table('funding_round_investor as mine')
            ->join('funding_round_investor as other', fn ($j) => $j->on('other.funding_round_id', '=', 'mine.funding_round_id')->on('other.investor_id', '!=', 'mine.investor_id'))
            ->join('investors', 'investors.id', '=', 'other.investor_id')
            ->where('mine.investor_id', $investor->id)
            ->groupBy('investors.id', 'investors.name', 'investors.slug', 'investors.type')
            ->orderByDesc('shared')->orderBy('investors.name')->limit(20)
            ->get(['investors.id', 'investors.name', 'investors.slug', 'investors.type', DB::raw('count(*) as shared')]);
        $stages = $rounds->countBy('round_type')->sortDesc();
        $disclosed = $rounds->whereNotNull('amount_usd');
        return view('funding.investor', compact('investor', 'rounds', 'coInvestors', 'stages', 'disclosed'));
    }

    // Founder and leadership movements from the officer register (see PeopleFlags).
    public function people(Request $request, \App\Services\Insights\PeopleFlags $flags)
    {
        $f = $request->validate(['kind' => 'nullable|in:' . implode(',', array_keys($flags::KINDS)), 'period' => 'nullable|in:' . implode(',', array_keys($flags::PERIODS)), 'q' => 'nullable|string|max:100']);
        $kind = $f['kind'] ?? 'ventures';
        $days = (int) ($f['period'] ?? 90);
        return view('funding.people', ['kind' => $kind, 'days' => $days, 'q' => $f['q'] ?? null,
            'rows' => $flags->flags($kind, $days, $f['q'] ?? null), 'counts' => blank($f['q'] ?? null) ? $flags->counts($days) : []]);
    }
}
