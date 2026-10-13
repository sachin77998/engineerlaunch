@extends('layouts.site')
@section('title', $company->name . ' — company insights, financials and ratios')
@push('styles')@include('insights.partials.styles')@endpush
@section('content')
@php
    $money = fn ($v) => \App\Services\Insights\CompanyInsights::money($v);
    $pct = fn ($v) => $v === null ? '—' : '<span class="' . ($v >= 0 ? 'up' : 'down') . '">' . ($v >= 0 ? '' : '−') . number_format(abs($v), 1) . '%</span>';
    $x = fn ($v) => $v === null ? '—' : number_format($v, 2) . '×';
    $detail = $company->registryDetail;
    $latest = $ratios->last();
    $isOwner = auth()->check() && (auth()->user()->role_code === 2 || auth()->user()->role === 'admin');
    $statements = $company->financialStatements->sortBy('financial_year')->groupBy('financial_year');
    $years = $statements->keys();
    $status = $detail?->company_status ?? $company->registry_status;
@endphp
<section class="ci-hero">
    <div class="ci-wrap">
        <div class="ci-head">
            <span class="ci-logo">@if($company->logo_url)<img src="{{ $company->logo_url }}" alt="">@else{{ mb_strtoupper(mb_substr($company->name, 0, 1)) }}@endif</span>
            <div>
                <h1>{{ $company->name }}</h1>
                <div class="ci-chips">
                    @if($cin)<span>CIN {{ $cin }}</span>@endif
                    @if($status)<span class="{{ preg_match('/^active/i', $status) ? 'ok' : (preg_match('/strike|dissol|liquid/i', $status) ? 'bad' : '') }}">{{ $status }}</span>@endif
                    @if($decoded)<span>{{ $decoded['Listing'] }}</span><span>{{ $decoded['Company type'] }}</span>@endif
                    @if($company->industry)<span>{{ $company->industry }}</span>@endif
                </div>
            </div>
        </div>
        <form class="ci-search" method="get" action="{{ route('insights.index') }}" style="margin-top:16px">
            <input type="search" name="q" placeholder="Search another company: name, CIN, GSTIN or PAN" aria-label="Search another company">
            <button class="ci-btn gold" type="submit">Search</button>
        </form>
    </div>
</section>
<main class="ci-wrap ci-main">
    @if(session('success'))<p class="ci-flash ok">{{ session('success') }}</p>@endif
    @if(session('warning'))<p class="ci-flash warn">{{ session('warning') }}</p>@endif
    @if($errors->any())<p class="ci-flash warn">{{ $errors->first() }}</p>@endif

    <nav class="ci-tabs">
        <a href="#overview">Overview</a><a href="#financials">Financials</a><a href="#ratios">Ratios</a><a href="#registry">Registry details</a>
        <a href="#people">Directors</a><a href="#charges">Charges</a><a href="#auditors">Auditors</a><a href="#business">Business</a><a href="#filings">Filings</a>
        <a href="{{ route('companies.show', $company) }}">Jobs at {{ Str::limit($company->name, 24) }}</a>
    </nav>

    <section class="ci-card" id="overview">
        <h2>Overview{{ $latest ? ' · FY ' . $latest['year'] : '' }}</h2>
        @if($latest)
            <div class="ci-metrics">
                <div class="ci-metric"><span>Revenue</span><b>{{ $money($latest['revenue']) }}</b><small>Growth {!! $pct($latest['revenue_growth']) !!}</small></div>
                <div class="ci-metric"><span>Net profit (PAT)</span><b class="{{ ($latest['pat'] ?? 0) < 0 ? 'down' : '' }}">{{ $money($latest['pat']) }}</b><small>Margin {!! $pct($latest['net_margin']) !!}</small></div>
                <div class="ci-metric"><span>EBITDA</span><b>{{ $money($latest['ebitda']) }}</b><small>Margin {!! $pct($latest['ebitda_margin']) !!}</small></div>
                <div class="ci-metric"><span>Net worth</span><b>{{ $money($latest['net_worth']) }}</b><small>Return on equity {!! $pct($latest['roe']) !!}</small></div>
                <div class="ci-metric"><span>Total debt</span><b>{{ $money($latest['total_debt']) }}</b><small>Debt / equity {{ $x($latest['debt_to_equity']) }}</small></div>
                <div class="ci-metric"><span>Total assets</span><b>{{ $money($latest['total_assets']) }}</b><small>ROA {!! $pct($latest['roa']) !!}</small></div>
                <div class="ci-metric"><span>ROCE</span><b>{!! $pct($latest['roce']) !!}</b><small>Interest cover {{ $x($latest['interest_cover']) }}</small></div>
                <div class="ci-metric"><span>EPS · P/E</span><b>{{ $latest['eps'] !== null ? '₹' . number_format($latest['eps'], 2) : '—' }} · {{ $latest['pe'] !== null ? number_format($latest['pe'], 1) : '—' }}</b><small>{{ $latest['pe'] === null ? 'P/E needs the share price (listed companies)' : 'At the recorded year-end price' }}</small></div>
            </div>
            @if($ratios->count() > 1)
                @php($peak = max(1, $ratios->max(fn ($r) => max(abs($r['revenue'] ?? 0), abs($r['pat'] ?? 0)))))
                <h3 style="margin:18px 0 4px;color:#0b2545;font-size:15px">Revenue and profit by year</h3>
                <div class="ci-bars" role="img" aria-label="Revenue and profit after tax by financial year">
                    @foreach($ratios as $r)
                        <div class="ci-bar" title="FY {{ $r['year'] }}: revenue {{ $money($r['revenue']) }}, PAT {{ $money($r['pat']) }}">
                            <div style="display:flex;gap:3px;align-items:flex-end;height:130px;width:100%;justify-content:center">
                                <i style="height:{{ max(2, abs($r['revenue'] ?? 0) / $peak * 100) }}%"></i>
                                <i class="pat {{ ($r['pat'] ?? 0) < 0 ? 'neg' : '' }}" style="height:{{ max(2, abs($r['pat'] ?? 0) / $peak * 100) }}%"></i>
                            </div>
                            <span>{{ $r['year'] }}</span>
                        </div>
                    @endforeach
                </div>
                <p class="ci-muted" style="font-size:12.5px;margin:6px 0 0"><span style="color:#d99a00">■</span> Revenue &nbsp; <span style="color:#1d4ed8">■</span> Profit after tax (red = loss)</p>
            @endif
        @else
            <p class="ci-muted">No financial statements are on record for this company yet. They come from its AOC-4 filings with the Ministry of Corporate Affairs.@if($isOwner) Upload an AOC-4 below, or enter a year's figures by hand.@endif</p>
        @endif
    </section>

    @if($years->isNotEmpty())
        <section class="ci-card" id="financials">
            <h2>Financial statements (standalone{{ $consolidatedRatios->isNotEmpty() ? '; consolidated figures in the second table of ratios' : '' }})</h2>
            <div class="ci-table-wrap">
                <table class="ci-table">
                    <thead><tr><th>₹ · FY</th>@foreach($years as $year)<th>{{ $year }}</th>@endforeach</tr></thead>
                    <tbody>
                    @foreach([
                        'Profit and loss' => null, 'revenue' => 'Revenue from operations', 'other_income' => 'Other income', 'total_expenses' => 'Total expenses', 'employee_cost' => 'Employee cost',
                        'finance_cost' => 'Finance cost', 'depreciation' => 'Depreciation', 'profit_before_tax' => 'Profit before tax', 'tax_expense' => 'Tax', 'profit_after_tax' => 'Profit after tax',
                        'Balance sheet' => null, 'share_capital' => 'Share capital', 'reserves' => 'Reserves / other equity', 'net_worth' => 'Net worth', 'long_term_borrowings' => 'Long-term borrowings',
                        'short_term_borrowings' => 'Short-term borrowings', 'total_debt' => 'Total debt', 'trade_payables' => 'Trade payables', 'current_liabilities' => 'Current liabilities',
                        'fixed_assets' => 'Fixed assets', 'inventories' => 'Inventories', 'trade_receivables' => 'Trade receivables', 'cash_and_bank' => 'Cash and bank',
                        'current_assets' => 'Current assets', 'total_assets' => 'Total assets',
                    ] as $field => $label)
                        @if($label === null)
                            <tr class="ci-sub"><td colspan="{{ $years->count() + 1 }}">{{ $field }}</td></tr>
                        @elseif($statements->contains(fn ($rows) => $rows->firstWhere('is_consolidated', false)?->{$field} !== null || $rows->first()?->{$field} !== null))
                            <tr><td>{{ $label }}</td>@foreach($years as $year)@php($s = $statements[$year]->firstWhere('is_consolidated', false) ?? $statements[$year]->first())<td>{{ $money($s->{$field}) }}</td>@endforeach</tr>
                        @endif
                    @endforeach
                    </tbody>
                </table>
            </div>
            <p class="ci-muted" style="font-size:12.5px">Source: {{ $company->financialStatements->pluck('source')->filter()->unique()->implode(', ') }} filings. Cr = crore, L = lakh.</p>
        </section>

        <section class="ci-card" id="ratios">
            <h2>Ratios</h2>
            @foreach(['Standalone' => $ratios, 'Consolidated' => $consolidatedRatios] as $title => $set)
                @continue($set->isEmpty())
                @if($consolidatedRatios->isNotEmpty())<h3 style="font-size:15px;color:#0b2545">{{ $title }}</h3>@endif
                <div class="ci-table-wrap" style="margin-bottom:12px">
                    <table class="ci-table">
                        <thead><tr><th>Ratio · FY</th>@foreach($set as $r)<th>{{ $r['year'] }}</th>@endforeach</tr></thead>
                        <tbody>
                            @foreach(['revenue_growth' => ['Revenue growth', 'pct'], 'profit_growth' => ['Profit growth', 'pct'], 'ebitda_margin' => ['EBITDA margin', 'pct'], 'net_margin' => ['Net profit margin', 'pct'],
                                      'roe' => ['Return on equity (ROE)', 'pct'], 'roce' => ['Return on capital employed (ROCE)', 'pct'], 'roa' => ['Return on assets', 'pct'],
                                      'debt_to_equity' => ['Debt / equity', 'x'], 'debt_to_ebitda' => ['Debt / EBITDA', 'x'], 'interest_cover' => ['Interest coverage', 'x'],
                                      'current_ratio' => ['Current ratio', 'x'], 'asset_turnover' => ['Asset turnover', 'x'], 'equity_multiplier' => ['Equity multiplier', 'x'], 'sales_to_fixed_assets' => ['Sales to fixed assets', 'x'], 'eps' => ['EPS (₹)', 'num'], 'pe' => ['P/E', 'num']] as $key => [$label, $kind])
                                <tr><td>{{ $label }}</td>@foreach($set as $r)<td>{!! $kind === 'pct' ? $pct($r[$key]) : ($kind === 'x' ? $x($r[$key]) : ($r[$key] === null ? '—' : number_format($r[$key], 2))) !!}</td>@endforeach</tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endforeach
            <p class="ci-muted" style="font-size:12.5px">ROE = PAT ÷ net worth · ROCE = (PBT + finance cost) ÷ (net worth + debt) · Debt includes long- and short-term borrowings · EBITDA = PBT + finance cost + depreciation when not stated.</p>
        </section>
    @endif

    <div class="ci-two">
        <section class="ci-card" id="registry">
            <h2>Registry details</h2>
            <div class="ci-facts">
                @foreach(array_filter([
                    'CIN' => $cin, 'PAN' => $detail?->pan, 'GSTIN' => $detail?->gstins ? implode(', ', $detail->gstins) : null, 'Status' => $status,
                    'Class' => $detail?->company_class, 'Category' => $detail?->company_category, 'Sub-category' => $detail?->company_subcategory,
                    'Listing' => $detail?->listing_status ?? ($decoded['Listing'] ?? null), 'Date of incorporation' => $detail?->incorporation_date?->format('d M Y') ?? ($decoded ? 'Year ' . $decoded['Year of incorporation'] : null),
                    'Registrar (ROC)' => $detail?->roc, 'State' => $decoded['State of registration'] ?? $company->registered_state, 'Industry code (NIC)' => $detail?->nic_code ?? ($decoded['Industry (NIC code)'] ?? null),
                    'Authorised capital' => $detail?->authorised_capital !== null ? $money($detail->authorised_capital) : null,
                    'Paid-up capital' => $detail?->paid_up_capital !== null ? $money($detail->paid_up_capital) : null,
                    'Members / shareholders' => $detail?->members_count ? number_format($detail->members_count) : null,
                    'Latest balance sheet' => $detail?->last_balance_sheet_date?->format('d M Y'), 'Email' => $detail?->email, 'Website' => $company->website,
                    'Registered office' => $detail?->registered_address ?? $company->headquarters,
                ]) as $label => $value)
                    <div><span>{{ $label }}</span><b>{{ $value }}</b></div>
                @endforeach
            </div>
        </section>

        <section class="ci-card" id="people">
            <h2>Directors and key people</h2>
            @forelse($company->officers->sortBy('name') as $officer)
                @if($loop->first)<div class="ci-table-wrap"><table class="ci-table"><thead><tr><th>Name</th><th>Designation</th><th>DIN</th><th>Appointed</th></tr></thead><tbody>@endif
                <tr><td>{{ $officer->name }}</td><td>{{ $officer->designation }}</td><td>{{ $officer->din ?? '—' }}</td><td>{{ $officer->appointed_on?->format('d M Y') ?? '—' }}</td></tr>
                @if($loop->last)</tbody></table></div>@endif
            @empty
                <p class="ci-muted">Directors are read from the MGT-7 annual return and AOC-4 signatories.</p>
            @endforelse
        </section>
    </div>

    <div class="ci-two">
        <section class="ci-card" id="charges">
            <h2>Charges (secured borrowings)</h2>
            @if($company->charges->isNotEmpty())
                @php($open = $company->charges->filter(fn ($c) => !preg_match('/satisf|closed/i', (string) $c->status)))
                <p class="ci-muted" style="margin-top:0">{{ $open->count() }} open · {{ $company->charges->count() - $open->count() }} satisfied · open amount {{ $money($open->sum('amount')) }}</p>
                <div class="ci-table-wrap"><table class="ci-table"><thead><tr><th>Charge holder</th><th>Amount</th><th>Status</th><th>Created</th><th>Satisfied</th></tr></thead><tbody>
                    @foreach($company->charges as $charge)<tr><td style="white-space:normal">{{ $charge->lender }}</td><td>{{ $money($charge->amount) }}</td><td>{{ $charge->status ?? '—' }}</td><td>{{ $charge->created_on?->format('d M Y') ?? '—' }}</td><td>{{ $charge->satisfied_on?->format('d M Y') ?? '—' }}</td></tr>@endforeach
                </tbody></table></div>
            @else
                <p class="ci-muted">Registered charges come from the MCA index of charges (InstaFinancials import).</p>
            @endif
        </section>
        <section class="ci-card" id="auditors">
            <h2>Statutory auditors</h2>
            @forelse($company->auditors as $auditor)
                @if($loop->first)<div class="ci-table-wrap"><table class="ci-table"><thead><tr><th>Audit firm</th><th>FY</th><th>Firm reg. no.</th><th>City</th></tr></thead><tbody>@endif
                <tr><td style="white-space:normal">{{ $auditor->name }}</td><td>{{ $auditor->financial_year ?? '—' }}</td><td>{{ $auditor->firm_registration_number ?? '—' }}</td><td>{{ $auditor->city ?? '—' }}</td></tr>
                @if($loop->last)</tbody></table></div>@endif
            @empty
                <p class="ci-muted">Auditors are read from the AOC-4 auditor's report section.</p>
            @endforelse
        </section>
    </div>

    <section class="ci-card" id="business">
        <h2>Business activities and related parties</h2>
        @if($company->businessProducts->isNotEmpty())
            <div class="ci-table-wrap" style="margin-bottom:12px"><table class="ci-table"><thead><tr><th>Product / service</th><th>Code</th><th>FY</th><th>% of turnover</th></tr></thead><tbody>
                @foreach($company->businessProducts->sortByDesc('turnover_percentage') as $product)<tr><td style="white-space:normal">{{ $product->description }}</td><td>{{ $product->product_code }}</td><td>{{ $product->financial_year }}</td><td>{{ $product->turnover_percentage !== null ? $product->turnover_percentage . '%' : '—' }}</td></tr>@endforeach
            </tbody></table></div>
        @endif
        @if($company->relatedParties->isNotEmpty())
            <div class="ci-table-wrap"><table class="ci-table"><thead><tr><th>Related party</th><th>Relationship</th><th>Transaction</th><th>Amount</th></tr></thead><tbody>
                @foreach($company->relatedParties as $party)<tr><td style="white-space:normal">{{ $party->party_name }}</td><td style="white-space:normal">{{ $party->relationship }}</td><td style="white-space:normal">{{ $party->transaction_description }}</td><td>{{ $money($party->amount) }}</td></tr>@endforeach
            </tbody></table></div>
        @endif
        @if($company->businessProducts->isEmpty() && $company->relatedParties->isEmpty())
            <p class="ci-muted">{{ $company->products ?: 'Principal business activities and related-party transactions are read from MGT-7 and AOC-4 filings.' }}</p>
        @endif
    </section>

    <section class="ci-card" id="filings">
        <h2>Filings</h2>
        @forelse($company->filings as $filing)
            <details class="ci-more" style="margin-bottom:8px">
                <summary>{{ $filing->form }} {{ $filing->financial_year }} · {{ $filing->created_at->format('d M Y') }} · {{ ucfirst($filing->status) }}</summary>
                @if($filing->message)<p class="ci-flash warn">{{ $filing->message }}</p>@endif
                @if($fieldsRead = $filing->parsed_fields['all_fields'] ?? [])
                    <p class="ci-muted" style="margin:8px 0 4px">All fields read from the filing ({{ count($fieldsRead) }})</p>
                    <div class="ci-table-wrap" style="max-height:340px;overflow:auto"><table class="ci-table"><tbody>
                        @foreach($fieldsRead as $key => $value)<tr><td style="white-space:normal">{{ ucfirst(str_replace('_', ' ', $key)) }}</td><td style="white-space:normal;text-align:left">{{ $value }}</td></tr>@endforeach
                    </tbody></table></div>
                @endif
            </details>
        @empty
            <p class="ci-muted">No filings uploaded yet.</p>
        @endforelse

        @if($isOwner)
            @if($cin && app(\App\Services\Insights\InstaFinancials::class)->configured())
                <form method="post" action="{{ route('insights.insta') }}" style="margin-top:12px">@csrf<input type="hidden" name="cin" value="{{ $cin }}"><button class="ci-btn light" type="submit">Refresh master data and directors from InstaFinancials (paid call)</button></form>
            @endif
            <h3 style="font-size:15px;color:#0b2545;margin-top:16px">Upload an MCA filing</h3>
            <p class="ci-muted">AOC-4 / AOC-4 XBRL (financial statements) or MGT-7 (annual return) as PDF or text. The PDF is read in your browser; image-only scans cannot be read.</p>
            <form method="post" action="{{ route('insights.upload', $company) }}" enctype="multipart/form-data" class="ci-inline" id="ci-upload">@csrf
                <select name="form" aria-label="Form type">@foreach($forms as $form)<option>{{ $form }}</option>@endforeach</select>
                <input type="file" name="file" accept=".pdf,.txt" required>
                <input type="hidden" name="text">
                <button class="ci-btn" type="submit">Upload and read</button>
                <small class="ci-muted" id="ci-upload-state"></small>
            </form>

            <details class="ci-more" style="margin-top:16px">
                <summary>Enter or correct a year's figures by hand</summary>
                <form method="post" action="{{ route('insights.statements', $company) }}" style="margin-top:10px">@csrf
                    <div class="ci-form-grid">
                        <label>Financial year<input type="text" name="financial_year" placeholder="2024-25" pattern="\d{4}-\d{2}" required></label>
                        <label>Amounts in<select name="unit"><option value="1">Rupees</option><option value="100000">Lakhs</option><option value="10000000" selected>Crores</option></select></label>
                        <label>Statement<select name="is_consolidated"><option value="0">Standalone</option><option value="1">Consolidated</option></select></label>
                        @foreach($fields as $field => $label)<label>{{ $label }}<input type="number" step="any" name="{{ $field }}"></label>@endforeach
                    </div>
                    <button class="ci-btn" type="submit" style="margin-top:10px">Save figures</button>
                </form>
            </details>
        @endif
    </section>
</main>
@endsection
@if($isOwner)
@push('scripts')
<script src="https://cdnjs.cloudflare.com/ajax/libs/pdf.js/3.11.174/pdf.min.js"></script>
<script>
(() => {
    const form = document.getElementById('ci-upload');
    if (!form || !window.pdfjsLib) return;
    pdfjsLib.GlobalWorkerOptions.workerSrc = 'https://cdnjs.cloudflare.com/ajax/libs/pdf.js/3.11.174/pdf.worker.min.js';
    const input = form.querySelector('input[type=file]'), state = document.getElementById('ci-upload-state'), button = form.querySelector('button');
    // Text of every page, one line per row of the form (items grouped by their vertical position).
    async function extract(file) {
        const pdf = await pdfjsLib.getDocument({data: await file.arrayBuffer()}).promise;
        const pages = [];
        for (let n = 1; n <= pdf.numPages; n++) {
            state.textContent = `Reading page ${n} of ${pdf.numPages}…`;
            const content = await (await pdf.getPage(n)).getTextContent();
            const rows = new Map();
            content.items.forEach(item => {
                if (!item.str.trim()) return;
                const y = Math.round(item.transform[5] / 3) * 3;
                if (!rows.has(y)) rows.set(y, []);
                rows.get(y).push([item.transform[4], item.str]);
            });
            pages.push([...rows.entries()].sort((a, b) => b[0] - a[0]).map(([, items]) => items.sort((a, b) => a[0] - b[0]).map(i => i[1]).join('  ')).join('\n'));
        }
        return pages.join('\n');
    }
    input.addEventListener('change', async () => {
        form.text.value = '';
        const file = input.files[0];
        if (!file || !/\.pdf$/i.test(file.name)) { state.textContent = ''; return; }
        button.disabled = true;
        try {
            const text = await extract(file);
            form.text.value = text;
            state.textContent = text.trim().length > 200 ? `Text ready (${text.length.toLocaleString()} characters).` : 'Very little text found: this may be a scanned PDF.';
        } catch (e) { state.textContent = 'Could not read this PDF in the browser (' + e.message + '). Upload the text version instead.'; }
        button.disabled = false;
    });
})();
</script>
@endpush
@endif
