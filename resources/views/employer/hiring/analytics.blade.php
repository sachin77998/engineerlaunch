@extends('layouts.employer')
@section('title','Hiring Analytics')
@include('employer.hiring._style')
@push('styles')<style>
.trend{display:grid;grid-template-columns:repeat(12,1fr);gap:6px;align-items:end;height:160px;margin-top:8px}.trend div{display:flex;flex-direction:column;justify-content:flex-end;align-items:center;height:100%;font-size:11px;color:var(--muted)}
.trend b{display:block;width:100%;background:#bcd3f7;border-radius:5px 5px 0 0;min-height:2px;position:relative}.trend b i{position:absolute;bottom:0;left:0;right:0;background:var(--blue);border-radius:5px 5px 0 0}
.funnel div{display:grid;grid-template-columns:110px 1fr 50px;gap:8px;align-items:center;margin-bottom:6px}.funnel .bar{height:18px;background:#e6eefa;border-radius:6px;overflow:hidden}.funnel .bar i{display:block;height:100%;background:var(--blue)}
.two{display:grid;grid-template-columns:1fr 1fr;gap:16px}@media(max-width:900px){.two{grid-template-columns:1fr}}
</style>@endpush
@section('content')
@php($inr = fn ($v) => $v === null ? '—' : '₹'.number_format($v))
<div class="hs-head">
    <div><h1>Hiring Analytics</h1><div class="muted">{{ $from->format('d M Y') }} – {{ $to->format('d M Y') }}</div></div>
    <form class="hs-row" method="get">
        <select class="hs-in" name="period" style="width:auto" onchange="this.form.submit()">@foreach(['30d' => 'Last 30 days', '90d' => 'Last 3 months', '6m' => 'Last 6 months', '1y' => 'Last 1 year', '3y' => 'Last 3 years', '5y' => 'Last 5 years'] as $k => $v)<option value="{{ $k }}" @selected($period === $k && !request('from'))>{{ $v }}</option>@endforeach</select>
        <input class="hs-in" type="date" name="from" value="{{ request('from') }}" style="width:auto"><input class="hs-in" type="date" name="to" value="{{ request('to') }}" style="width:auto"><button class="hs-mini">Apply</button>
    </form>
</div>

<div class="kpis">
    <div class="kpi"><span>Applications</span><b>{{ number_format($m['received']) }}</b><small>{{ $m['waiting'] }} new waiting &gt; 3 days</small></div>
    <div class="kpi"><span>Hires</span><b>{{ $m['hires'] }}</b><small>{{ $m['conversion'] !== null ? $m['conversion'].'% of applicants' : '' }}</small></div>
    <div class="kpi"><span>Cost per hire</span><b>{{ $inr($m['cost_per_hire']) }}</b><small>Spend {{ $inr($m['spend']) }}</small></div>
    <div class="kpi"><span>Agency cost avoided</span><b>{{ $inr($m['agency_cost']) }}</b><small>{{ $m['hires'] }} × {{ $inr($m['cost']->agency_fee_per_hire) }}</small></div>
    <div class="kpi"><span>Time to hire</span><b>{{ $m['days_to_hire'] !== null ? $m['days_to_hire'].' d' : '—' }}</b><small>First response {{ $m['days_to_action'] !== null ? $m['days_to_action'].' d' : '—' }}</small></div>
    <div class="kpi"><span>Auto-screened</span><b>{{ number_format($m['auto_screened']) }}</b><small>{{ $m['auto_shortlisted'] }} shortlisted · {{ $m['auto_rejected'] }} rejected</small></div>
    <div class="kpi"><span>Recruiter hours saved</span><b>{{ $m['hours_saved'] }} h</b><small>≈ {{ $m['recruiters_freed'] }} recruiter(s) · {{ $inr($m['money_saved']) }}</small></div>
    <div class="kpi"><span>Interviews</span><b>{{ $m['interviews']->sum() }}</b><small>{{ $m['interviews']['completed'] ?? 0 }} done · {{ $m['interviews']['no_show'] ?? 0 }} no-show</small></div>
</div>

<div class="two">
    <section class="hs-card">
        <h2>Pipeline (applications in period, by current stage)</h2>
        @php($max = max(1, $m['stages']->max() ?? 1))
        <div class="funnel">@foreach($m['stage_labels'] as $k => $label)@php($c = (int) ($m['stages'][$k] ?? 0))<div><span>{{ $label }}</span><span class="bar"><i style="width:{{ round($c / $max * 100) }}%"></i></span><strong>{{ $c }}</strong></div>@endforeach</div>
    </section>
    <section class="hs-card">
        <h2>Last 12 months</h2>
        @php($tmax = max(1, collect($m['months'])->max('apps')))
        <div class="trend">@foreach($m['months'] as $ym => $v)<div title="{{ $v['apps'] }} applications, {{ $v['hired'] }} hired"><b style="height:{{ max(2, round($v['apps'] / $tmax * 130)) }}px"><i style="height:{{ $v['apps'] ? round($v['hired'] / $v['apps'] * 100) : 0 }}%"></i></b>{{ \Carbon\Carbon::createFromFormat('Y-m', $ym)->format('M') }}</div>@endforeach</div>
        <div class="muted" style="font-size:12px;margin-top:6px">Light: applications · Dark: share hired</div>
    </section>
</div>

<section class="hs-card hs-scroll">
    <h2>By job</h2>
    <table class="hs-table"><thead><tr><th>Job</th><th>Status</th><th>Applications</th><th>Shortlisted+</th><th>Interviewed+</th><th>Hired</th><th>Avg score</th><th></th></tr></thead><tbody>
    @forelse($m['by_job'] as $j)<tr><td><a href="{{ route('employer.hiring.pipeline', $j->id) }}">{{ $j->title }}</a></td><td>{{ ucfirst($j->status) }}</td><td>{{ $j->apps }}</td><td>{{ (int) $j->shortlisted }}</td><td>{{ (int) $j->interviewed }}</td><td>{{ (int) $j->hired }}</td><td>{{ $j->score ?? '—' }}</td><td><a class="hs-mini" href="{{ route('employer.hiring.talent', ['job_id' => $j->id]) }}">Open</a></td></tr>
    @empty<tr><td colspan="8" class="muted" style="text-align:center;padding:24px">No applications in this period.</td></tr>@endforelse
    </tbody></table>
</section>

<div class="two">
    <section class="hs-card">
        <h2>By source</h2>
        <table class="hs-table"><thead><tr><th>Source</th><th>Applications</th><th>Hired</th></tr></thead><tbody>
        @forelse($m['by_source'] as $s)<tr><td>{{ ucwords(str_replace('_', ' ', $s->source)) }}</td><td>{{ $s->apps }}</td><td>{{ (int) $s->hired }}</td></tr>@empty<tr><td colspan="3" class="muted">—</td></tr>@endforelse
        </tbody></table>
    </section>
    <form class="hs-card" method="post" action="{{ route('employer.hiring.costs') }}">@csrf
        <h2>Your hiring cost inputs</h2>
        <div class="hs-grid">
            <div><label>Recruiters in team</label><input type="number" min="0" name="recruiters" value="{{ $m['cost']->recruiters ?? 1 }}" required></div>
            <div><label>Monthly cost per recruiter (₹)</label><input type="number" min="0" name="monthly_cost_per_recruiter" value="{{ (int) $m['cost']->monthly_cost_per_recruiter }}" required></div>
            <div><label>Monthly tools / ATS spend (₹)</label><input type="number" min="0" name="monthly_tools_spend" value="{{ (int) $m['cost']->monthly_tools_spend }}" required></div>
            <div><label>Monthly job board spend (₹)</label><input type="number" min="0" name="monthly_job_board_spend" value="{{ (int) $m['cost']->monthly_job_board_spend }}" required></div>
            <div><label>Agency fee per hire (₹)</label><input type="number" min="0" name="agency_fee_per_hire" value="{{ (int) $m['cost']->agency_fee_per_hire }}" required></div>
            <div><label>Minutes to screen one CV manually</label><input type="number" min="1" max="120" name="minutes_per_manual_screen" value="{{ $m['cost']->minutes_per_manual_screen ?? 6 }}" required></div>
        </div>
        <button class="hs-mini primary" style="margin-top:10px">Save inputs</button>
    </form>
</div>
@endsection
