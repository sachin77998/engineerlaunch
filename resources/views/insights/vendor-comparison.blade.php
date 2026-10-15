@extends('layouts.site')
@section('title','Vendor comparison')
@push('styles')@include('insights.partials.styles')@endpush
@section('content')
<div class="ci-wrap" style="padding-top:30px">
<h1>Vendor comparison &amp; payment history</h1>
<p><a href="{{ route('insights.index') }}">Company insights</a></p>
@if(session('status'))<p class="ci-flash">{{ session('status') }}</p>@endif
@foreach($errors->all() as $error)<p class="ci-risk">{{ $error }}</p>@endforeach
@if(!$ready)<p class="ci-flash warn">History storage is not installed. Run the vendor observations migration before importing records.</p>@endif
<section class="ci-card"><h2>Compare up to 10 companies</h2>
<form method="get">
<label>Find company <input name="q" value="{{ request('q') }}" maxlength="100"></label>
<button class="ci-btn">Search / compare</button>
<p><label>Companies <select name="companies[]" multiple size="7">
@foreach($selected->merge($companies)->unique('id') as $company)<option value="{{ $company->id }}" @selected($selected->contains('id',$company->id))>{{ $company->name }}</option>@endforeach
</select></label></p>
<label>Relationship <select name="kind">@foreach(['vendor_refund'=>'Vendor returns our money','customer_payment'=>'Customer pays us','supplier_delivery'=>'Supplier delivers material'] as $value=>$label)<option value="{{ $value }}" @selected($kind===$value)>{{ $label }}</option>@endforeach</select></label>
<label>Material / service (exact match) <input name="material" value="{{ $material }}" maxlength="100"></label>
<label>Currency <input name="currency" value="{{ $currency }}" maxlength="3" size="4"></label>
<button class="ci-btn">Compare</button>
</form>
<p>Payments are compared against agreed due dates. Supplier delivery and quality are assessed separately. Only compare records with matching material, currency and relationship.</p>
<div class="ci-table-wrap"><table class="ci-table"><thead><tr><th>Company</th><th>Records</th><th>Agreed days (avg)</th><th>Actual days (avg)</th><th>Late days (completed avg)</th><th>Open overdue</th><th>On-time %*</th><th>Quality accepted / known</th><th>Assessment</th></tr></thead><tbody>
@forelse($comparisons as $row)<tr><td>{{ $row['company']->name }}</td>
@foreach(['count','average_term','average_days','average_delay','open_overdue','on_time'] as $key)<td>{{ $row['metrics'][$key]??'Unknown' }}</td>@endforeach
<td>{{ $row['metrics']['quality_accepted'] }} / {{ $row['metrics']['quality_known'] }}</td><td>{{ $row['metrics']['status'] }}</td></tr>
@empty<tr><td colspan="9">Select companies to compare. No history means no payment reliability ranking.</td></tr>@endforelse
</tbody></table></div>
<p class="ci-muted">*Completed on time divided by completed plus open overdue records, as of {{ now()->toDateString() }}. Future-due open records are excluded. This is observed performance, not an agency credit rating.</p>
</section>
<section class="ci-card"><h2>ML readiness</h2><p>{{ $model['status'] }}. Training records: {{ $model['training'] }}; holdout: {{ $model['holdout'] }}. Model error (days): {{ $model['mae']??'Not evaluated' }}; median baseline error: {{ $model['baseline_mae']??'Not evaluated' }}. No default probability is inferred. The experimental model only evaluates completed outcomes; open unpaid obligations remain in the comparison above.</p><p>Collect real outcomes with evidence before training and chronological validation. Financial filings alone cannot establish payment history, fraud or supplier quality.</p></section>
<section class="ci-card"><h2>Add evidence</h2><p>Private owner-only records. Use actual contractual dates and source references. Duplicate references are rejected without importing any rows.</p>
<form method="post" action="{{ route('insights.vendors.store') }}">@csrf
<div class="ci-grid">
<label>Company <select name="company_id" required>@foreach($selected->merge($companies)->unique('id') as $company)<option value="{{ $company->id }}">{{ $company->name }}</option>@endforeach</select></label>
<label>Reference <input name="reference" required maxlength="100"></label>
<label>Relationship <select name="kind"><option value="vendor_refund">Vendor refund</option><option value="customer_payment">Customer payment</option><option value="supplier_delivery">Supplier delivery</option></select></label>
<label>Material / service <input name="material" required maxlength="100"></label>
<label>Issued / agreed <input name="issued_on" type="date" required></label>
<label>Contractual due date <input name="due_on" type="date" required></label>
<label>Completed / fully settled <input name="completed_on" type="date"></label>
<label>Amount <input name="amount" type="number" min="0.01" step="0.01" required></label>
<label>Currency <input name="currency" value="INR" maxlength="3" required></label>
<label>Delivery quality <select name="quality"><option value="">Unknown / not applicable</option><option value="accepted">Accepted</option><option value="rejected">Rejected</option></select></label>
<label>Evidence reference <input name="evidence" maxlength="500" required></label>
</div><p>Leave completion blank until fully settled or delivered; partial payment is not completion.</p><button class="ci-btn" @disabled(!$ready)>Save record</button></form>
<h3>CSV import</h3><a href="{{ route('insights.vendors.template') }}">Download empty CSV template</a>
<form method="post" enctype="multipart/form-data" action="{{ route('insights.vendors.store') }}">@csrf
<input type="file" name="csv" accept=".csv" required><button class="ci-btn" @disabled(!$ready)>Import (maximum 500 rows)</button></form>
</section></div>
@endsection
