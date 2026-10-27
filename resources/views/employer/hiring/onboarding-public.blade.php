@extends('employer.hiring._public')
@section('title','Joining documents')
@section('content')
@php($co = $o->application?->job?->company?->name)
@php($docs = $o->items->where('kind', 'document'))
<h1>Welcome to {{ $co ?? 'the team' }}</h1>
<p class="muted">{{ $o->application?->candidate?->name }} · {{ $o->application?->job?->title }}@if($o->due_on) · please finish by {{ $o->due_on->format('d M Y') }}@endif</p>
@if(session('success'))<div class="ok">{{ session('success') }}</div>@endif
@foreach($errors->all() as $e)<div class="err">{{ $e }}</div>@endforeach
@php($done = $docs->filter(fn ($i) => in_array($i->status, ['submitted', 'approved'], true))->count())
<div style="margin:14px 0 6px;font-weight:700">{{ $done }} of {{ $docs->count() }} documents uploaded</div>
<div style="height:8px;border-radius:8px;background:#e6eefa;overflow:hidden"><div style="height:100%;width:{{ $docs->count() ? round($done / $docs->count() * 100) : 100 }}%;background:#2563eb"></div></div>
<p class="muted" style="font-size:13px">PDF, JPG or PNG, up to 5 MB each. Files are visible only to the company's hiring team.</p>
@foreach($docs as $item)
    <div style="border:1px solid var(--line);border-radius:12px;padding:14px;margin:12px 0">
        <div style="display:flex;justify-content:space-between;gap:10px;align-items:flex-start">
            <div><strong>{{ $item->label }}</strong>@unless($item->required) <span class="muted">(optional)</span>@endunless
                @if($item->help)<div class="muted" style="font-size:13px">{{ $item->help }}</div>@endif</div>
            @php($badge = ['pending' => ['To upload', '#9a5b00', '#fef6e7'], 'submitted' => ['Uploaded · under review', '#2457bd', '#eef4ff'], 'approved' => ['Approved', '#0b7a43', '#e8f7ef'], 'rejected' => ['Please upload again', '#b42318', '#fdecea']][$item->status] ?? [$item->status, '#667085', '#f2f4f7'])
            <span style="white-space:nowrap;font-size:12px;font-weight:700;padding:3px 9px;border-radius:30px;color:{{ $badge[1] }};background:{{ $badge[2] }}">{{ $badge[0] }}</span>
        </div>
        @if($item->status === 'rejected' && $item->review_note)<div class="err" style="margin:10px 0 0">{{ $item->review_note }}</div>@endif
        @if($item->file_name && $item->status !== 'rejected')<div class="muted" style="font-size:13px;margin-top:6px">File: {{ $item->file_name }}</div>@endif
        @if($item->status !== 'approved')
            <form method="post" action="{{ route('hiring.onboarding.upload', [$token, $item]) }}" enctype="multipart/form-data" style="display:flex;gap:8px;align-items:center;margin-top:10px;flex-wrap:wrap">@csrf
                <input type="file" name="file" accept=".pdf,.jpg,.jpeg,.png,application/pdf,image/jpeg,image/png" required style="flex:1;min-width:200px">
                <button class="btn primary">{{ $item->file_path ? 'Replace' : 'Upload' }}</button>
            </form>
        @endif
    </div>
@endforeach
@endsection
