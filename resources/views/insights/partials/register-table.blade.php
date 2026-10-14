<div class="ci-table-wrap">
    <table class="ci-table">
        <thead><tr><th>Company</th><th>Status</th><th>Class</th><th>State</th><th>Activity</th><th>Incorporated</th><th>Paid-up capital</th></tr></thead>
        <tbody>
        @forelse($rows as $r)
            <tr>
                <td style="white-space:normal;min-width:240px"><a href="{{ route('insights.cin', $r->cin) }}"><strong>{{ \Illuminate\Support\Str::title(\Illuminate\Support\Str::lower($r->name)) }}</strong></a><br><small class="ci-muted">{{ $r->cin }}</small></td>
                <td>@if($r->status)<span class="{{ preg_match('/^active/i', $r->status) ? 'up' : 'down' }}">{{ ucfirst(strtolower($r->status)) }}</span>@else — @endif</td>
                <td style="white-space:normal">{{ ($r->cin[0] ?? '') === 'L' ? 'Listed' : 'Unlisted' }} · {{ $r->company_class ?? '—' }}</td>
                <td>{{ $r->state ? \Illuminate\Support\Str::title(\Illuminate\Support\Str::lower($r->state)) : '—' }}</td>
                <td style="white-space:normal;max-width:260px">{{ $r->activity ?? '—' }}</td>
                <td>{{ $r->registered_on ? \Carbon\Carbon::parse($r->registered_on)->format('d M Y') : '—' }}</td>
                <td>{{ \App\Services\Insights\CompanyInsights::money($r->paid_up_capital) }}</td>
            </tr>
        @empty
            <tr><td colspan="7">No companies match these filters.</td></tr>
        @endforelse
        </tbody>
    </table>
</div>
