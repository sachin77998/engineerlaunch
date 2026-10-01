<!doctype html>
<html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Employer job feeds</title>
<style>body{font:16px system-ui;color:#173453;background:#f5f7fb;margin:0}header{background:#0d1b35;padding:22px;color:white}header a{color:white}main{max-width:1250px;margin:24px auto;padding:0 16px}section{background:white;border:1px solid #dce5ef;border-radius:12px;padding:20px;margin-bottom:20px}.table{overflow:auto}table{border-collapse:collapse;width:100%}th,td{text-align:left;padding:12px;border-bottom:1px solid #e3e8ef;vertical-align:top}a{color:#1756ba}button{background:#2563eb;color:white;border:0;border-radius:8px;padding:12px 18px;font:inherit;cursor:pointer}button:disabled{opacity:.5}small{display:block;margin-top:6px;max-width:360px;overflow-wrap:anywhere}</style></head>
<body><header><a href="{{ route('admin.dashboard') }}">Admin dashboard</a> / Employer job feeds</header><main>
<h1>Employer job feeds</h1>
@if(session('source_status'))<section role="status">{{ session('source_status') }}</section>@endif
<section><p>{{ $inventory->count() }} requested employers. {{ $companies->sum('jobs_count') }} currently published openings from these employers.</p>
<form method="post" action="{{ route('admin.job-sources.refresh') }}">@csrf<button @disabled(!$ready)>Queue employer refresh</button></form>
@if(!$ready)<p>Database deployment is incomplete. Queue and audit tables must be installed before refreshing.</p>@endif
<p>Queued jobs require the cPanel scheduler to run. A listed company does not mean its careers feed has successfully imported vacancies.</p></section>
@if($run)<section><h2>Latest batch #{{ $run->id }}</h2><p>{{ $run->status }} &middot; {{ $run->successful_items }} successful &middot; {{ $run->failed_items }} failed &middot; {{ $run->pending_items }} pending</p><p>Started: {{ $run->started_at }} &middot; Updated: {{ $run->updated_at }}</p><p>{{ $run->records_created }} added / {{ $run->records_updated }} updated</p></section>@endif
<section class="table"><table><thead><tr><th>Employer</th><th>Industry</th><th>Published jobs</th><th>Feed</th><th>Last complete sync</th><th>Latest batch result</th></tr></thead><tbody>
@foreach($inventory as $source)
@php($company=$companies->get($source['name']))
@php($item=$company ? $items->get($company->id) : null)
<tr><td><a href="{{ $company?->careers_url ?: $source['careers_url'] }}" target="_blank" rel="noopener noreferrer">{{ $source['name'] }}</a></td><td>{{ $source['industry'] }}</td><td>{{ $company?->jobs_count ?? 0 }}</td><td>{{ $company?->ats_provider ?: 'Not configured' }}@if($company && !$company->sync_enabled)<small>Disabled</small>@endif</td><td>{{ $company?->last_synced_at ?: 'Not yet completed' }}</td><td>{{ $item?->status ?: 'Not run in this batch' }}@if($item)<small>{{ $item->records_created }} added, {{ $item->records_updated }} updated</small>@if($item->failure_reason)<small>{{ \Illuminate\Support\Str::limit($item->failure_reason,400) }}</small>@endif @endif</td></tr>
@endforeach
</tbody></table></section></main></body></html>
