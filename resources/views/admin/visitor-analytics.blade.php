<section class="panel" id="visitor-analytics" style="margin:20px 0;padding:24px;background:white;border:1px solid #e4e9f2;border-radius:12px">
    <h2>Website visitors</h2>
    <form method="get" action="{{ route('admin.dashboard') }}#visitor-analytics" style="display:flex;gap:12px;align-items:end;flex-wrap:wrap">
        @foreach(request()->only(['city','salary_min','salary_max']) as $key=>$value)
            <input type="hidden" name="{{ $key }}" value="{{ $value }}">
        @endforeach
        <label>Date ({{ $visitorAnalytics['timezone'] }})<br>
            <input type="date" name="visit_date" value="{{ $visitorAnalytics['date'] }}" max="{{ now(config('visitor_analytics.timezone'))->toDateString() }}" required>
        </label>
        <button type="submit">Show visitors</button>
        <a href="{{ route('admin.dashboard') }}#visitor-analytics">Today</a>
    </form>
    <div style="display:flex;gap:40px;margin:20px 0;flex-wrap:wrap">
        <div><strong style="font-size:28px">{{ number_format($visitorAnalytics['visitors']) }}</strong><br>Unique visitors</div>
        <div><strong style="font-size:28px">{{ number_format($visitorAnalytics['page_views']) }}</strong><br>Page views</div>
    </div>
    <p>Visitors are counted once per browser each day. Known bots and admin pages are excluded.</p>
    <h3>Visitors by state</h3>
    <div style="overflow-x:auto">
        <table style="width:100%;text-align:left"><thead><tr><th>State / province</th><th>Country</th><th>Visitors</th><th>Page views</th></tr></thead><tbody>
        @forelse($visitorAnalytics['states'] as $row)
            <tr><td>{{ $row->state ?: 'Unknown' }}</td><td>{{ $row->country_code ?: 'Unknown' }}</td><td>{{ number_format($row->visitors) }}</td><td>{{ number_format($row->page_views) }}</td></tr>
        @empty
            <tr><td colspan="4">No visits recorded for this date.</td></tr>
        @endforelse
        </tbody></table>
    </div>
    <p style="color:#64748b">State is an approximate IP location. Unknown includes unavailable lookups. IP location by <a href="https://db-ip.com" target="_blank" rel="noopener noreferrer">DB-IP</a>. Tracking starts after this update is deployed.</p>
    <details><summary>Daily totals - last 30 days</summary>
        <table style="width:100%;text-align:left"><thead><tr><th>Date</th><th>Unique visitors</th><th>Page views</th></tr></thead><tbody>
        @foreach($visitorAnalytics['days'] as $day)
            <tr><td><a href="{{ route('admin.dashboard', ['visit_date'=>$day['date']]) }}#visitor-analytics">{{ $day['date'] }}</a></td><td>{{ number_format($day['visitors']) }}</td><td>{{ number_format($day['page_views']) }}</td></tr>
        @endforeach
        </tbody></table>
    </details>
</section>
