@extends('layouts.site')
@section('title', 'Production Setup')
@push('styles')<style>
.mt{width:min(1100px,calc(100% - 32px));margin:28px auto 60px}
.mt h1{margin:0 0 6px}.mt-sub{color:var(--muted);margin:0 0 18px}
.mt-actions{display:flex;flex-wrap:wrap;gap:10px;margin-bottom:18px}
.mt-step{display:grid;grid-template-columns:28px 1fr auto;gap:12px;align-items:center;background:#fff;border:1px solid var(--line);border-radius:12px;padding:12px 14px;margin-bottom:8px}
.mt-dot{width:14px;height:14px;border-radius:50%;background:#cbd5e1}.mt-dot.run{background:#f4b400;animation:mtp 1s infinite}.mt-dot.ok{background:#16a34a}.mt-dot.fail{background:#dc2626}
@keyframes mtp{50%{opacity:.4}}
.mt-step code{font-size:12px;color:var(--muted)}.mt-step small{color:var(--muted)}
.mt-out{grid-column:1/-1;max-height:260px;overflow:auto;background:#0b1220;color:#d6e2f2;border-radius:8px;padding:10px;font:12px/1.5 ui-monospace,Consolas,monospace;white-space:pre-wrap;margin:6px 0 0}
.mt-out:empty{display:none}
.mt-btn{padding:9px 14px;border-radius:9px;border:1px solid var(--line);background:#fff;cursor:pointer;font:inherit;font-weight:600}
details.mt-log{margin-top:22px}details.mt-log pre{max-height:420px;overflow:auto;background:#0b1220;color:#d6e2f2;border-radius:10px;padding:12px;font:12px/1.5 ui-monospace,Consolas,monospace;white-space:pre-wrap}
</style>@endpush
@section('content')
<div class="mt">
    <h1>Production Setup</h1>
    <p class="mt-sub">Runs these commands on this server. "Run all" goes top to bottom and continues past failures.</p>
    <div class="mt-actions">
        <button class="primary" type="button" id="mt-all">Run all</button>
        <a class="mt-btn" href="{{ route('sectors.index') }}" target="_blank">Open Companies by Sector ↗</a>
        <a class="mt-btn" href="{{ route('admin.deploy-log') }}" target="_blank">Raw deployment log ↗</a>
    </div>
    @foreach($steps as $key => [$label, $command, $arguments])
        <div class="mt-step" data-step="{{ $key }}">
            <span class="mt-dot"></span>
            <div><strong>{{ $label }}</strong><br><code>php artisan {{ $command }}</code> <small class="mt-time"></small></div>
            <button class="mt-btn" type="button" data-run="{{ $key }}">Run</button>
            <pre class="mt-out"></pre>
        </div>
    @endforeach
    <details class="mt-log" @if($deployLog) open @endif><summary><strong>Last deployment log</strong></summary><pre>{{ $deployLog ?: 'No deployment log yet.' }}</pre></details>
</div>
@endsection
@push('scripts')<script>
(() => {
    const token = '{{ csrf_token() }}';
    const url = step => '{{ url('/admin/maintenance/run') }}/' + step;
    async function run(step) {
        const row = document.querySelector(`[data-step="${step}"]`);
        const dot = row.querySelector('.mt-dot'), out = row.querySelector('.mt-out'), time = row.querySelector('.mt-time');
        dot.className = 'mt-dot run'; out.textContent = ''; time.textContent = 'running…';
        try {
            const res = await fetch(url(step), {method: 'POST', headers: {'X-CSRF-TOKEN': token, 'Accept': 'application/json'}});
            const data = await res.json().catch(() => ({exit: 1, output: 'HTTP ' + res.status + ' (the server may have timed out; check again or run the step on its own)'}));
            dot.className = 'mt-dot ' + (data.exit === 0 ? 'ok' : 'fail');
            out.textContent = data.output || '(no output)';
            time.textContent = data.seconds !== undefined ? `· ${data.seconds}s · exit ${data.exit}` : '';
            return data.exit === 0;
        } catch (e) {
            dot.className = 'mt-dot fail'; out.textContent = String(e); time.textContent = ''; return false;
        }
    }
    document.querySelectorAll('[data-run]').forEach(b => b.addEventListener('click', () => run(b.dataset.run)));
    document.getElementById('mt-all').addEventListener('click', async e => {
        e.target.disabled = true;
        for (const row of document.querySelectorAll('[data-step]')) await run(row.dataset.step);
        e.target.disabled = false;
    });
})();
</script>@endpush
