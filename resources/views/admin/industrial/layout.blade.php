@extends('layouts.site')
@section('title', 'Industrial Area Manager')
@push('styles')
<style>.im-wrap{width:min(1200px,calc(100% - 32px));margin:32px auto}.im-tabs,.im-actions{display:flex;gap:10px;flex-wrap:wrap;margin:18px 0}.im-tabs a{padding:10px 14px;border-radius:8px;background:#e5efff;color:#173f80;text-decoration:none}.im-panel{background:white;border:1px solid #dbe4f0;border-radius:12px;padding:22px;margin:20px 0}.im-table{overflow:auto}.im-table table{width:100%;border-collapse:collapse}.im-table th,.im-table td{text-align:left;padding:12px;border-bottom:1px solid #dbe4f0;vertical-align:top}.im-grid{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:18px}.im-grid label{display:block;margin-bottom:6px;font-weight:650}.im-grid .wide{grid-column:1/-1}.im-note{color:#526176;line-height:1.7}.im-success{padding:16px;background:#dcfce7;color:#14532d;border-radius:8px}.im-form small{display:block;color:#526176;margin:8px 0}.im-table input{min-width:160px}.im-pages{display:flex;justify-content:space-between;margin:18px 0}@media(max-width:650px){.im-grid{grid-template-columns:1fr}.im-panel{padding:14px}}</style>
@endpush
@section('content')
<div class="im-wrap"><p><a href="{{ route('admin.dashboard') }}">Owner dashboard</a> / <a href="{{ route('industrial.index') }}">Public industrial directory</a></p><h1>Industrial Area Manager</h1><nav class="im-tabs" aria-label="Industrial administration">@foreach($labels as $key=>$label)<a href="{{ route('admin.industrial.index', ['type'=>$key]) }}">{{ $label }}</a>@endforeach<a href="{{ route('admin.industrial.cities') }}">Cities / Districts</a><a href="{{ route('admin.industrial.sources') }}">Sources</a><a href="{{ route('admin.industrial.sources', ['status'=>'unverified']) }}">Verification</a></nav>
@if(session('status'))<p class="im-success" role="status">{{ session('status') }}</p>@endif
@if($errors->any())<div class="error" role="alert"><ul>@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>@endif
@yield('manager')
</div>
@endsection
