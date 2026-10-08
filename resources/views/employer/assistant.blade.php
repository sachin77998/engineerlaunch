@extends('layouts.employer')
@section('title', $flow === 'company' ? 'Company Profile Assistant' : 'Job Posting Assistant')
@push('styles')<style>
.ea{max-width:780px;margin:28px auto;padding:0 16px}.ea-card{background:#fff;border:1px solid var(--line);border-radius:18px;padding:24px;box-shadow:0 10px 30px rgba(37,99,235,.07)}
.ea-top{display:flex;justify-content:space-between;align-items:center;gap:12px;margin-bottom:6px}.ea-top h1{margin:0;font-size:22px}
.ea-tabs{display:flex;gap:8px;margin:0 0 18px}.ea-tabs a{padding:6px 12px;border-radius:999px;border:1px solid var(--line);font-size:13px}.ea-tabs a.on{background:var(--blue);color:#fff;border-color:var(--blue)}
.ea-progress{height:6px;background:#e8f0fe;border-radius:6px;overflow:hidden;margin-bottom:16px}.ea-progress span{display:block;height:100%;background:var(--blue);transition:width .3s}
.ea-log{max-height:52vh;overflow:auto;padding-right:4px}
.ea-b{padding:12px 16px;border-radius:16px;margin:8px 0;line-height:1.5;max-width:82%;animation:ea-in .25s ease}.ea-bot{background:#eaf1ff;border-bottom-left-radius:4px}.ea-me{margin-left:auto;background:var(--navy);color:#fff;border-bottom-right-radius:4px}
@keyframes ea-in{from{opacity:0;transform:translateY(4px)}to{opacity:1;transform:none}}
.ea-form{display:flex;gap:10px;margin-top:14px;align-items:flex-start}.ea-form input,.ea-form textarea,.ea-form select{flex:1;padding:11px 13px;border:1px solid var(--line);border-radius:10px;font:inherit}.ea-form textarea{min-height:96px}
.ea-btn{padding:11px 18px;border:0;border-radius:10px;background:var(--blue);color:#fff;font-weight:700;cursor:pointer}.ea-ghost{padding:9px 14px;border:1px solid var(--line);border-radius:10px;background:#fff;cursor:pointer;font:inherit}
.ea-chips{display:flex;flex-wrap:wrap;gap:8px;margin-top:14px}.ea-chips button{padding:8px 14px;border:1px solid var(--blue);border-radius:999px;background:#fff;color:var(--blue);cursor:pointer;font:inherit}.ea-chips button:hover{background:var(--blue);color:#fff}
.ea-row{display:flex;gap:8px;margin-top:12px}.ea-err{color:#b42318;margin-top:8px;font-size:13px}
.ea-review{display:grid;grid-template-columns:1fr 1fr;gap:10px;margin-top:10px}.ea-review div{padding:10px 12px;background:#f8fafc;border-radius:9px}.ea-review small{color:var(--muted)}
@media(max-width:640px){.ea-review{grid-template-columns:1fr}.ea-b{max-width:100%}.ea-form{flex-direction:column}.ea-form>*{width:100%}}
</style>@endpush
@php
    $total = count($keys);
@endphp
@section('content')
<div class="ea"><div class="ea-card">
    <div class="ea-top">
        <h1>{{ $flow === 'company' ? 'Build your company profile' : 'Post a job' }}</h1>
        <form method="post" action="{{ route('employer.assistant.reset', $flow) }}">@csrf<button class="ea-ghost">Start over</button></form>
    </div>
    <div class="ea-tabs">
        <a href="{{ route('employer.assistant', 'company') }}" class="{{ $flow === 'company' ? 'on' : '' }}">Company profile</a>
        <a href="{{ route('employer.assistant', 'job') }}" class="{{ $flow === 'job' ? 'on' : '' }}">Job posting</a>
        <a href="{{ route('employer.dashboard') }}">Dashboard</a>
    </div>
    <div class="ea-progress"><span style="width:{{ $total ? round(min($index, $total) / $total * 100) : 0 }}%"></span></div>

    <div class="ea-log" id="ea-log">
        <div class="ea-b ea-bot">{{ $flow === 'company' ? 'Hi! Answer a few questions and I will create your company profile, including your plant location so job seekers can find you by city and industrial area.' : 'Let us create a job listing for '.($profile?->company?->name ?? 'your company').'. I will suggest roles your sector usually hires for.' }}</div>
        @foreach($keys as $i => $key)
            @if($i < $index)
                <div class="ea-b ea-bot">{{ $steps[$key][0] }}</div>
                <div class="ea-b ea-me">{{ filled($answers[$key] ?? null) ? ($display[$key] ?? $answers[$key]) : 'Skipped' }}</div>
            @endif
        @endforeach
        @if(!$review)<div class="ea-b ea-bot">{{ $steps[$current][0] }}</div>@endif
    </div>

    @if(!$review)
        @php $type = $steps[$current][1]; @endphp
        @if($type === 'select' && $options)
            <form method="post" action="{{ route('employer.assistant.answer', $flow) }}" class="ea-chips">@csrf
                @foreach($options as $value => $text)<button name="answer" value="{{ $value }}">{{ $text }}</button>@endforeach
            </form>
        @else
            <form method="post" action="{{ route('employer.assistant.answer', $flow) }}" class="ea-form">@csrf
                @if($type === 'textarea')
                    <textarea name="answer" autofocus>{{ old('answer', $default) }}</textarea>
                @else
                    <input type="{{ $type }}" name="answer" value="{{ old('answer', $default) }}" @if($suggestions) list="ea-suggest" @endif autofocus autocomplete="off">
                    @if($suggestions)<datalist id="ea-suggest">@foreach($suggestions as $s)<option value="{{ $s }}">@endforeach</datalist>@endif
                @endif
                <button class="ea-btn">Send</button>
            </form>
        @endif
        @error('answer')<div class="ea-err">{{ $message }}</div>@enderror
        @if($index > 0)<form method="post" action="{{ route('employer.assistant.back', $flow) }}" class="ea-row">@csrf<button class="ea-ghost">Back</button></form>@endif
    @else
        <div class="ea-b ea-bot"><strong>Here is a summary.</strong> Nothing is saved until you confirm.</div>
        <div class="ea-review">@foreach($steps as $key => $step)<div><small>{{ $step[0] }}</small><br><strong>{{ filled($answers[$key] ?? null) ? ($display[$key] ?? $answers[$key]) : '—' }}</strong></div>@endforeach</div>
        <div class="ea-row">
            <form method="post" action="{{ route('employer.assistant.complete', $flow) }}">@csrf<button class="ea-btn">{{ $flow === 'company' ? 'Save company profile' : (($answers['action'] ?? '') === 'publish' ? 'Publish job' : 'Save draft') }}</button></form>
            <form method="post" action="{{ route('employer.assistant.back', $flow) }}">@csrf<button class="ea-ghost">Edit last answer</button></form>
        </div>
    @endif
</div></div>
@endsection
@push('scripts')<script>const l=document.getElementById('ea-log');if(l)l.scrollTop=l.scrollHeight;</script>@endpush
