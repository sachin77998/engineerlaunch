@extends('layouts.site')
@section('title', 'State Approvals Manager')
@push('styles')
<style>.im-wrap{width:min(1200px,calc(100% - 32px));margin:32px auto}.im-actions{display:flex;gap:10px;flex-wrap:wrap;margin:12px 0;align-items:center}.im-panel{background:white;border:1px solid #dbe4f0;border-radius:12px;padding:18px;margin:16px 0}.im-table{overflow:auto}.im-table table{width:100%;border-collapse:collapse}.im-table th,.im-table td{text-align:left;padding:9px;border-bottom:1px solid #dbe4f0;vertical-align:top;font-size:14px}.im-grid{display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:10px}.im-grid label{display:block;margin-bottom:4px;font-weight:650;font-size:13px}.im-note{color:#526176;font-size:13px}.im-success{padding:14px;background:#dcfce7;color:#14532d;border-radius:8px}.im-table input,.im-table select,.im-table textarea{width:100%;min-width:120px}.im-pages{display:flex;justify-content:space-between;margin:14px 0}@media(max-width:760px){.im-grid{grid-template-columns:1fr}.im-panel{padding:12px}}</style>
@endpush
@section('content')
<div class="im-wrap"><p><a href="{{ route('admin.dashboard') }}">Owner dashboard</a> / <a href="{{ route('admin.approvals.index') }}">State approvals</a> / <a href="{{ route('approvals.index') }}">Public page</a></p>
@if(session('status'))<p class="im-success" role="status">{{ session('status') }}</p>@endif
@if($errors->any())<div class="error" role="alert"><ul>@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>@endif
@yield('manager')
</div>
@endsection
