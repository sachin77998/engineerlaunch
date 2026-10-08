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
    <div class="mt-step" style="grid-template-columns:1fr"><div><strong>Server PHP</strong><br><small>Web PHP {{ $phpVersion }}. PHP command-line binaries found: {{ $phpPaths ? implode(', ', $phpPaths) : 'none of the usual paths' }}.</small>
        @if($phpPaths)<br><small>Cron job command: <code>{{ $phpPaths[0] }} {{ $basePath }}/artisan schedule:run &gt;&gt; {{ $basePath }}/storage/logs/scheduler.log 2&gt;&amp;1</code></small>@endif</div></div>
    <details class="mt-log" @if($deployLog) open @endif><summary><strong>Last deployment log</strong></summary><pre>{{ $deployLog ?: 'No deployment log yet.' }}</pre></details>
</div>
@endsection
@push('scripts')<script>
(() => {
    const token = '{{ csrf_token() }}';
    const url = step => '{{ url('/admin/maintenance/run') }}/' + step;
    const pause = ms => new Promise(r => setTimeout(r, ms));
    async function call(step) {
        const res = await fetch(url(step), {method: 'POST', headers: {'X-CSRF-TOKEN': token, 'Accept': 'application/json'}});
        return res.json().catch(() => ({exit: 1, http: res.status, output: 'HTTP ' + res.status + ' - the server stopped this request (time or resource limit). Wait a minute, then press Run on this step again.'}));
    }
    // Long seeders work in ~18 s slices and print [continue]; keep calling until they finish.
    async function run(step) {
        const row = document.querySelector(`[data-step="${step}"]`);
        const dot = row.querySelector('.mt-dot'), out = row.querySelector('.mt-out'), time = row.querySelector('.mt-time');
        dot.className = 'mt-dot run'; out.textContent = ''; time.textContent = 'running…';
        let total = 0, pass = 0, data;
        try {
            do {
                pass++;
                data = await call(step);
                total += Number(data.seconds || 0);
                out.textContent += (pass > 1 ? '\n--- pass ' + pass + ' ---\n' : '') + (data.output || '(no output)');
                out.scrollTop = out.scrollHeight;
                time.textContent = `· ${total.toFixed(1)}s · pass ${pass}` + (data.exit !== undefined ? ` · exit ${data.exit}` : '');
                if (data.exit === 0 && /\[continue\]/.test(data.output || '')) await pause(1500); else break;
            } while (pass < 100);
            dot.className = 'mt-dot ' + (data.exit === 0 ? 'ok' : 'fail');
            return data.exit === 0 ? 'ok' : (data.http ? 'down' : 'fail');
        } catch (e) {
            dot.className = 'mt-dot fail'; out.textContent += '\nThe server did not respond (' + e + '). Wait a minute and run this step again.'; time.textContent = '';
            return 'down';
        }
    }
    document.querySelectorAll('[data-run]').forEach(b => b.addEventListener('click', () => run(b.dataset.run)));
    document.getElementById('mt-all').addEventListener('click', async e => {
        e.target.disabled = true;
        let downInARow = 0;
        for (const row of document.querySelectorAll('[data-step]')) {
            const result = await run(row.dataset.step);
            downInARow = result === 'down' ? downInARow + 1 : 0;
            // Shared hosting throttles bursts: stop instead of failing every remaining step.
            if (downInARow >= 2) { alertBox('The server is busy or throttling requests. Wait 2-3 minutes, then press Run on the first red step and continue from there.'); break; }
            await pause(1200);
        }
        e.target.disabled = false;
    });
    function alertBox(text) {
        let box = document.getElementById('mt-alert');
        if (!box) { box = document.createElement('p'); box.id = 'mt-alert'; box.style.cssText = 'padding:12px 14px;border-radius:10px;background:#fff6dc;border:1px solid #f4b400;color:#0b2545;font-weight:600'; document.querySelector('.mt-actions').after(box); }
        box.textContent = text; box.scrollIntoView({behavior: 'smooth', block: 'center'});
    }
})();
</script>@endpush
