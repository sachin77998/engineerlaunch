@extends('layouts.site')
@section('title', $service->name . ' — ' . $state->name . ' document checklist')
@include('approvals._styles')
@php
    $j = fn ($v) => json_decode((string) $v, true) ?: [];
    // A checklist line with no Y/N marker is required unless it carries a condition.
    $req = fn ($d) => $d->is_required === null ? ($d->source === 'checklist' && $d->kind === 'document' ? ($d->condition ? ['c', 'If applicable'] : ['y', 'Required']) : ['', '']) : ($d->is_required ? ['y', 'Mandatory'] : ($d->condition || $d->kind !== 'document' ? ['c', 'If applicable'] : ['n', 'Optional']));
    $items = $checklist->whereIn('kind', ['document', 'option', 'noc']);
    $notes = $checklist->where('kind', 'note');
    $coded = $checklist->whereNotNull('code')->isNotEmpty();
    $links = array_filter(['Document checklist' => $service->checklist_url, 'Application form' => $service->form_url, 'Fee structure' => $service->fee_url, 'Procedure (SOP)' => $service->sop_url, 'Timelines' => $service->timeline_url, 'Inspection checklist' => $service->inspection_url]);
@endphp
@section('content')
<div class="sa-wrap">
    <p class="sa-crumbs"><a href="{{ route('approvals.index') }}">State approvals</a> / <a href="{{ route('approvals.state', $state->slug) }}">{{ $state->name }}</a> / {{ $service->department }}</p>
    <h1>{{ $service->name }}</h1>

    <section class="sa-card sa-kv">
        <div><span>Department</span>{{ $service->department ?: '—' }}</div>
        <div><span>Type</span>{{ $service->service_type ?: '—' }}</div>
        <div><span>Stage</span>{{ implode(', ', $j($service->stages)) ?: '—' }}</div>
        <div><span>Applied</span>{{ $service->integration ?: '—' }}</div>
        @foreach(['Unit size' => 'unit_sizes', 'Business location' => 'locations', 'Risk / pollution category' => 'risk_categories', 'Investor type' => 'investor_types'] as $label => $col)
            @if($j($service->$col))<div><span>{{ $label }}</span>{{ implode(', ', $j($service->$col)) }}</div>@endif
        @endforeach
    </section>

    @if($links)
        <div class="sa-links">@foreach($links as $label => $url)<a href="{{ $url }}" target="_blank" rel="noopener nofollow">{{ $label }}</a>@endforeach
            @if($source?->portal_url)<a href="{{ $source->portal_url }}" target="_blank" rel="noopener nofollow">Apply on {{ $source->portal_name }}</a>@endif</div>
    @endif

    @if($items->isNotEmpty())
        <section class="sa-card"><h2>Documents to submit</h2>
            <div class="sa-table"><table>
                <thead><tr><th>#</th><th>Document</th><th>{{ $coded ? 'Department checks' : 'Condition' }}</th><th>Required</th></tr></thead>
                <tbody>
                    @php($n = 0)
                    @foreach($items->groupBy(fn ($d) => $d->group_label ?? '') as $group => $rows)
                        @if($group !== '')<tr><td></td><td colspan="3" class="sa-group">{{ $group }}{{ $rows->first()->kind === 'option' ? ' — any one' : '' }}</td></tr>@endif
                        @foreach($rows as $d)
                            @php([$cls, $txt] = $req($d))
                            <tr><td>{{ $group === '' ? ++$n : '' }}</td>
                                <td>{{ $d->name }}@if($d->code)<div class="sa-sub">{{ $d->code }}</div>@endif</td>
                                <td>{{ $d->condition ?: '—' }}</td>
                                <td><span class="sa-req {{ $cls }}">{{ $txt ?: '—' }}</span></td></tr>
                        @endforeach
                    @endforeach
                </tbody>
            </table></div>
            @if($notes->isNotEmpty())<ul class="sa-sub" style="margin:12px 0 0;padding-left:18px;line-height:1.7">@foreach($notes as $nt)<li>{{ $nt->name }}</li>@endforeach</ul>@endif
        </section>
    @elseif($notes->isNotEmpty())
        <section class="sa-card"><h2>Requirements</h2><ul style="margin:0;padding-left:18px;line-height:1.8">@foreach($notes as $nt)<li>{{ $nt->name }}</li>@endforeach</ul></section>
    @endif

    @if($portalDocs->isNotEmpty())
        <section class="sa-card"><h2>Documents accepted on the portal</h2>
            <div class="sa-table"><table>
                <thead><tr><th>Document</th><th>Type</th><th>Issued by</th><th>Required</th></tr></thead>
                <tbody>@foreach($portalDocs as $d)@php([$cls, $txt] = $req($d))
                    <tr><td>{{ $d->name }}@if($d->code)<div class="sa-sub">{{ $d->code }}</div>@endif @if($d->condition)<div class="sa-sub">{{ $d->condition }}</div>@endif</td><td>{{ $d->doc_type ?: '—' }}</td><td>{{ $d->issued_by ?: '—' }}</td><td><span class="sa-req {{ $cls }}">{{ $txt ?: '—' }}</span></td></tr>
                @endforeach</tbody>
            </table></div>
        </section>
    @endif

    @if($items->isEmpty() && $notes->isEmpty() && $portalDocs->isEmpty())
        <section class="sa-card">The department has not published a document list for this approval.@if($service->checklist_url) <a href="{{ $service->checklist_url }}" target="_blank" rel="noopener nofollow">Open the checklist PDF</a>.@endif</section>
    @endif

    @if($related->isNotEmpty())
        <section class="sa-card"><h2>More from {{ $service->department }}</h2>
            <ul class="sa-list">@foreach($related as $r)<li><a class="sa-name" href="{{ route('approvals.service', [$state->slug, $r->external_id]) }}">{{ $r->name }}</a></li>@endforeach</ul></section>
    @endif
</div>
@endsection
