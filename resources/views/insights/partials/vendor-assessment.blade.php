<section class="ci-card" id="vendor-checks">
    <h2>Vendor checks &amp; payment capacity</h2>
@if($isOwner ?? false)<p><a class="ci-btn" href="{{ route('insights.vendors.index',['companies'=>[$company->id]]) }}">Compare vendors / add payment history</a></p>@endif
    <p class="ci-flash warn">{{ $vendorAssessment['decision'] }}</p>
    <div class="ci-table-wrap"><table class="ci-table"><thead><tr><th>Verification check</th><th>Status</th><th>Evidence needed / available</th></tr></thead><tbody>
    @foreach($vendorAssessment['checks'] as $check)<tr><td>{{ $check['name'] }}</td><td>{{ $check['status'] }}</td><td>{{ $check['detail'] }}</td></tr>@endforeach
    </tbody></table></div>
    <h3>Payment-capacity indicators</h3>
    <p class="ci-muted">{{ $vendorAssessment['basis'] ?? 'Standalone' }} FY {{ $vendorAssessment['year'] ?? 'not available' }}; period ending {{ $vendorAssessment['period_end'] ?? 'unknown' }}. Missing inputs remain unknown.</p>
    <div class="ci-table-wrap"><table class="ci-table"><thead><tr><th>Indicator</th><th>Value</th><th>Calculation / limitation</th></tr></thead><tbody>
    @foreach($vendorAssessment['metrics'] as $metric)<tr><td>{{ $metric['name'] }}</td><td>{{ $metric['value'] === null ? 'Unknown' : number_format($metric['value'],2).'x' }}</td><td>{{ $metric['formula'] }}</td></tr>@endforeach
    </tbody></table></div>
    @foreach($vendorAssessment['risks'] as $risk)<p class="ci-risk">{{ $risk }}</p>@endforeach
    <h3>Financial trends &amp; future outlook</h3>
    <div class="ci-table-wrap"><table class="ci-table"><thead><tr><th>Financial year</th><th>Revenue</th><th>Profit after tax</th><th>Revenue change YoY</th><th>Source</th></tr></thead><tbody>
    @forelse($vendorAssessment['trends'] as $trend)<tr><td>{{ $trend['year'] }}</td><td>{{ $money($trend['revenue']) }}</td><td>{{ $money($trend['profit']) }}</td><td>{{ $trend['growth'] === null ? 'Not comparable' : $trend['growth'].'%' }}</td><td>{{ $trend['source'] }}</td></tr>@empty<tr><td colspan="5">No standalone history available.</td></tr>@endforelse
    </tbody></table></div>
    <details><summary>Explore a revenue stress scenario</summary>
        <form method="get" action="#vendor-checks"><label for="vendor-revenue-change">Revenue change (%)</label>
        <input id="vendor-revenue-change" name="vendor_revenue_change" type="number" min="-100" max="100" step="0.1" value="{{ $vendorAssessment['scenario']['change'] }}">
        <button class="ci-btn" type="submit">Calculate scenario</button></form>
        <p>Scenario revenue: {{ $money($vendorAssessment['scenario']['revenue']) }}; EBIT: {{ $money($vendorAssessment['scenario']['ebit']) }}; interest coverage: {{ $vendorAssessment['scenario']['interest_cover'] === null ? 'Unknown' : number_format($vendorAssessment['scenario']['interest_cover'],2).'x' }}.</p>
    </details>
    @if($vendorAssessment['reports']->isNotEmpty())<h3>Rating-agency documents</h3>@endif
    @foreach($vendorAssessment['reports'] as $report)
        @php($evidence = $report->parsed_fields ?? [])
        <details class="ci-card"><summary>{{ $evidence['agency'] ?? 'Agency unknown' }} | {{ $evidence['report_date'] ?? 'Date unknown' }} | Unverified uploaded evidence</summary>
        <p>{{ $report->original_name ?? 'Text report' }}: {{ $report->message }}</p>
        @if($evidence['withdrawal_mentioned'] ?? false)<p class="ci-flash warn">Withdrawal appears in the report summary. Do not present historical ratings as current. Withdrawal alone does not establish default or fraud.</p>@endif
        <p>{{ $evidence['notice'] ?? '' }}</p>
        <pre style="white-space:pre-wrap;overflow-wrap:anywhere;max-width:100%">{{ $evidence['summary_excerpt'] ?? 'No summary extracted.' }}</pre>
        </details>
    @endforeach
</section>
