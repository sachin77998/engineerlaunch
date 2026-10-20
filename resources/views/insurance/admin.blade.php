@extends('layouts.site')
@section('title', 'Insurer figures')
@push('styles')@include('calculators.styles')
<style>.ia-row input{width:86px;padding:6px;border:1px solid #d5deea;border-radius:6px;font:inherit}.ia-row input.wide{width:200px}.ia-save{padding:6px 12px;border:0;border-radius:8px;background:#0b2545;color:#fff;font-weight:700;cursor:pointer}
.pm-flash{padding:12px 14px;border-radius:10px;margin-bottom:16px;background:#e9f7ef;color:#0b5d33}</style>@endpush
@section('content')
<section class="cx-hero"><div class="cx-wrap"><h1>Insurer figures</h1><p>Enter each insurer's figures from the latest IRDAI annual report (claim settlement ratio, incurred claim ratio, complaints) and its public disclosures (network hospitals, solvency). The insurance advisor ranks insurers by these.</p></div></section>
<main class="cx-wrap cx-main">
    @if(session('success'))<p class="pm-flash">{{ session('success') }}</p>@endif
    @if($errors->any())<p class="pm-flash" style="background:#fef3f2;color:#b42318">{{ $errors->first() }}</p>@endif
    <div class="cx-scroll" style="max-height:none"><table class="cx-table">
        <thead><tr><th>Insurer</th><th>Sector</th><th>CSR %</th><th>ICR %</th><th>Complaints/10k</th><th>Hospitals</th><th>Solvency</th><th>Year</th><th>Source</th><th>Active</th><th></th></tr></thead>
        <tbody>
        @foreach($insurers as $insurer)
            <tr class="ia-row">
                <td style="white-space:normal;max-width:220px">{{ $insurer->name }}<br><small>{{ $insurer->products }}</small>
                    <form id="ins-{{ $insurer->id }}" method="post" action="{{ route('admin.insurers.update', $insurer) }}">@csrf @method('PATCH')</form></td><td>{{ $insurer->sector }}</td>
                <td><input form="ins-{{ $insurer->id }}" name="claim_settlement_ratio" type="number" step="0.01" value="{{ $insurer->claim_settlement_ratio }}"></td>
                <td><input form="ins-{{ $insurer->id }}" name="incurred_claim_ratio" type="number" step="0.01" value="{{ $insurer->incurred_claim_ratio }}"></td>
                <td><input form="ins-{{ $insurer->id }}" name="complaints_per_10k" type="number" step="0.01" value="{{ $insurer->complaints_per_10k }}"></td>
                <td><input form="ins-{{ $insurer->id }}" name="network_hospitals" type="number" step="1" value="{{ $insurer->network_hospitals }}"></td>
                <td><input form="ins-{{ $insurer->id }}" name="solvency_ratio" type="number" step="0.01" value="{{ $insurer->solvency_ratio }}"></td>
                <td><input form="ins-{{ $insurer->id }}" name="metrics_year" value="{{ $insurer->metrics_year }}" placeholder="2024-25"></td>
                <td><input form="ins-{{ $insurer->id }}" class="wide" name="metrics_source" value="{{ $insurer->metrics_source }}" placeholder="IRDAI Annual Report 2024-25"></td>
                <td><input form="ins-{{ $insurer->id }}" type="checkbox" name="is_active" value="1" @checked($insurer->is_active) style="width:auto"></td>
                <td><button class="ia-save" form="ins-{{ $insurer->id }}">Save</button></td>
            </tr>
        @endforeach
        </tbody>
    </table></div>
</main>
@endsection
