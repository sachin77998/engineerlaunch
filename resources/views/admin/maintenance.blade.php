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
.mt-dot.queued{background:#93c5fd}
.mt-refresh{background:#fff;border:1px solid var(--line);border-radius:14px;padding:16px;margin-bottom:18px}
.mt-refresh-head{display:flex;flex-wrap:wrap;gap:12px;justify-content:space-between;align-items:center}.mt-refresh-head small{color:var(--muted)}
.mt-progress{height:8px;border-radius:4px;background:#e5ecf4;margin:12px 0;overflow:hidden}.mt-progress span{display:block;height:100%;width:0;background:linear-gradient(90deg,#0b2545,#f4b400);transition:width .4s}
.mt-modules{display:grid;grid-template-columns:repeat(auto-fill,minmax(250px,1fr));gap:10px}
.mt-module{border:1px solid var(--line);border-radius:10px;padding:10px 12px}.mt-module h3{margin:0 0 6px;font-size:15px;color:#0b2545}
.mt-module ul{list-style:none;margin:0;padding:0;display:grid;gap:6px}.mt-module li{display:grid;grid-template-columns:14px 1fr;gap:8px;align-items:start;font-size:13px}
.mt-module li small{grid-column:2;color:var(--muted);font-size:11.5px}.mt-module .mt-dot{width:10px;height:10px;margin-top:4px}
.mt-worker{margin:10px 0 0;font-size:12.5px;color:var(--muted)}
details.mt-log{margin-top:22px}details.mt-log pre{max-height:420px;overflow:auto;background:#0b1220;color:#d6e2f2;border-radius:10px;padding:12px;font:12px/1.5 ui-monospace,Consolas,monospace;white-space:pre-wrap}
</style>@endpush
@section('content')
<div class="mt">
    <h1>Production Setup</h1>
    <p class="mt-sub">Quick steps run immediately; long steps are queued and finished by the background worker (cron), so they never hit the server's time limit. Status updates on its own.</p>
    <section class="mt-refresh">
        <div class="mt-refresh-head">
            <div><strong>Refresh everything</strong><br><small id="mt-refresh-meta">Fetches the latest jobs, companies, news, technologies and prices for every module in one queued batch.</small></div>
            <button class="primary" type="button" id="mt-refresh-all">Refresh everything</button>
        </div>
        <div class="mt-progress"><span id="mt-refresh-bar"></span></div>
        <div class="mt-modules">
            @foreach(\App\Http\Controllers\AdminMaintenanceController::REFRESH_MODULES as $module => [$moduleLabel, $tasks])
                <div class="mt-module" data-module="{{ $module }}"><h3>{{ $moduleLabel }}</h3>
                    <ul>@foreach($tasks as $label => $task)<li data-task="{{ $loop->index }}"><span class="mt-dot"></span><span>{{ $label }}</span><small></small></li>@endforeach</ul>
                </div>
            @endforeach
        </div>
        <p class="mt-worker" id="mt-worker"></p>
    </section>
    <div class="mt-actions">
        <button class="primary" type="button" id="mt-all">Run all</button>
        <a class="mt-btn" href="{{ route('sectors.index') }}" target="_blank">Open Companies by Sector ↗</a>
        <a class="mt-btn" href="{{ route('admin.deploy-log') }}" target="_blank">Raw deployment log ↗</a>
    </div>
    <div class="mt-step" style="grid-template-columns:1fr" id="mt-market">
        <div><strong>Market data upload</strong><br><small>Monthly model-wise vehicle sales (company releases, SIAM wholesale, FADA retail) or commodity prices (copper, steel, sand, gold by city). Download a template, fill it, upload. Existing rows for the same model/month or commodity/day are updated.</small>
        @if(session('success'))<p style="color:#087443;font-weight:700;margin:6px 0">{{ session('success') }}</p>@endif
        @error('file')<p style="color:#b42318;font-weight:700;margin:6px 0">{{ $message }}</p>@enderror
        <form method="post" action="{{ route('admin.market.upload') }}" enctype="multipart/form-data" style="display:flex;flex-wrap:wrap;gap:8px;align-items:center;margin-top:8px">@csrf
            <select name="type" class="mt-btn"><option value="vehicles">Vehicle sales</option><option value="prices">Commodity prices</option></select>
            <input type="file" name="file" accept=".csv" required>
            <button class="primary" type="submit">Upload CSV</button>
            <a class="mt-btn" href="{{ route('admin.market.template', 'vehicles') }}">Vehicle template</a>
            <a class="mt-btn" href="{{ route('admin.market.template', 'prices') }}">Price template</a>
            <a class="mt-btn" href="{{ route('market.index') }}" target="_blank">Open Market Data ↗</a>
        </form></div>
    </div>
    <div class="mt-step" style="grid-template-columns:1fr" id="mt-repos">
        <div><strong>GitHub repository on this server</strong><br>
        @forelse($repos as $i => $repo)
            <small><b>{{ $repo['name'] }}</b> · {{ $repo['head'] ?: 'unknown commit' }} · {{ $repo['has_cpanel_yml'] ? '.cpanel.yml found' : '.cpanel.yml MISSING' }} ·
            @if($repo['changes'])<span style="color:#b42318">{{ count($repo['changes']) }} local change(s) disable cPanel's Deploy button: {{ implode(', ', array_slice($repo['changes'], 0, 5)) }}</span>@else clean @endif</small>
            <div class="mt-actions" style="margin:8px 0 0"><button class="mt-btn" type="button" data-repo="{{ $i }}" data-action="clean">Remove local changes</button><button class="primary" type="button" data-repo="{{ $i }}" data-action="deploy">Pull from GitHub &amp; deploy</button></div>
        @empty
            <small>No cPanel repository found under ~/repositories, or this server does not allow running git from PHP. Use cPanel → Git Version Control instead.</small>
        @endforelse
        </div>
        <pre class="mt-out" id="mt-repo-out"></pre>
    </div>
    @foreach($steps as $key => [$label, $command, $arguments])
        <div class="mt-step" data-step="{{ $key }}">
            <span class="mt-dot"></span>
            <div><strong>{{ $label }}</strong><br><code>php artisan {{ $command }}</code> <small class="mt-time"></small></div>
            <button class="mt-btn" type="button" data-run="{{ $key }}" data-inline="{{ in_array($key, \App\Http\Controllers\AdminMaintenanceController::INLINE, true) ? 1 : 0 }}">{{ in_array($key, \App\Http\Controllers\AdminMaintenanceController::INLINE, true) ? 'Run' : 'Queue' }}</button>
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
    document.querySelectorAll('[data-repo]').forEach(b => b.addEventListener('click', async () => {
        const out = document.getElementById('mt-repo-out');
        b.disabled = true; out.textContent = 'Working… (deploy can take a minute)';
        try {
            const res = await fetch('{{ url('/admin/maintenance/repository') }}/' + b.dataset.repo + '/' + b.dataset.action, {method: 'POST', headers: {'X-CSRF-TOKEN': token, 'Accept': 'application/json'}});
            const data = await res.json();
            out.textContent = (data.exit === 0 ? 'Done. Reload this page to see the new state.\n\n' : 'Finished with errors.\n\n') + data.output;
        } catch (e) { out.textContent = 'The server did not respond (' + e + '). Check the deployment log below in a minute.'; }
        b.disabled = false;
    }));
    // Long steps go to the background queue; the page then follows their status.
    async function enqueue(step) {
        const res = await fetch('{{ url('/admin/maintenance/queue') }}/' + step, {method: 'POST', headers: {'X-CSRF-TOKEN': token, 'Accept': 'application/json'}});
        if (!res.ok) { alertBox('Could not queue (HTTP ' + res.status + '). Wait a minute and try again.'); return; }
        (await res.json()).queued.forEach(key => paint(key, {state: 'queued'}));
        poll();
    }
    const labels = {queued: 'queued · waiting for the background worker', running: 'running…', done: 'done', failed: 'failed'};
    function paint(key, s) {
        const row = document.querySelector(`[data-step="${key}"]`);
        if (!row) return;
        row.querySelector('.mt-dot').className = 'mt-dot ' + ({queued: 'queued', running: 'run', done: 'ok', failed: 'fail'}[s.state] || '');
        row.querySelector('.mt-time').textContent = '· ' + (labels[s.state] || s.state) + (s.seconds !== undefined ? ` · ${s.seconds}s` : '') + (s.finished_at ? ` · ${s.finished_at}` : '');
        if (s.output) row.querySelector('.mt-out').textContent = s.output;
    }
    let polling = null;
    async function poll() {
        clearTimeout(polling);
        let data;
        try { data = await (await fetch('{{ route('admin.maintenance.status') }}', {headers: {'Accept': 'application/json'}})).json(); } catch (e) { polling = setTimeout(poll, 10000); return; }
        let active = false;
        Object.entries(data.steps || {}).forEach(([key, s]) => { paint(key, s); active ||= ['queued', 'running'].includes(s.state); });
        Object.entries(data.modules || {}).forEach(([module, tasks]) => tasks.forEach((t, i) => {
            const li = document.querySelector(`[data-module="${module}"] [data-task="${i}"]`);
            if (!li) return;
            li.querySelector('.mt-dot').className = 'mt-dot ' + ({queued: 'queued', running: 'run', done: 'ok', failed: 'fail'}[t.state] || '');
            li.querySelector('small').textContent = t.state === 'idle' ? '' : (labels[t.state] || t.state) + (t.finished_at ? ' · ' + t.finished_at : '') + (t.state === 'failed' && t.output ? ' · ' + t.output.slice(-160) : '');
            active ||= ['queued', 'running'].includes(t.state);
        }));
        if (data.refresh) {
            document.getElementById('mt-refresh-bar').style.width = (data.refresh.progress ?? 0) + '%';
            document.getElementById('mt-refresh-meta').textContent = `Last started ${data.refresh.started_at} · ${data.refresh.progress ?? 0}% complete` + (data.refresh.finished ? ' · finished' : '') + (data.refresh.failed ? ` · ${data.refresh.failed} failed` : '');
        }
        document.getElementById('mt-worker').textContent = data.pending ? `${data.pending} job(s) waiting. The cron worker picks them up every minute; nothing runs if the cron job below is not set up.` : '';
        if (active || data.pending) polling = setTimeout(poll, 5000);
    }
    document.getElementById('mt-refresh-all').addEventListener('click', async e => {
        e.target.disabled = true;
        const res = await fetch('{{ route('admin.maintenance.refresh-all') }}', {method: 'POST', headers: {'X-CSRF-TOKEN': token, 'Accept': 'application/json'}});
        if (!res.ok) alertBox('Could not start the refresh (HTTP ' + res.status + '). Wait a minute and try again.');
        e.target.disabled = false;
        poll();
    });
    document.querySelectorAll('[data-run]').forEach(b => b.addEventListener('click', () => b.dataset.inline === '1' ? run(b.dataset.run) : enqueue(b.dataset.run)));
    document.getElementById('mt-all').addEventListener('click', async e => {
        e.target.disabled = true;
        // Quick steps run now, top to bottom; then every long step is queued in one request.
        for (const b of document.querySelectorAll('[data-run][data-inline="1"]')) {
            if (b.dataset.run === 'https') continue;
            if (await run(b.dataset.run) === 'down') { alertBox('The server is busy. Wait 2-3 minutes and press Run all again.'); e.target.disabled = false; return; }
            await pause(800);
        }
        await enqueue('all');
        e.target.disabled = false;
    });
    poll();
    function alertBox(text) {
        let box = document.getElementById('mt-alert');
        if (!box) { box = document.createElement('p'); box.id = 'mt-alert'; box.style.cssText = 'padding:12px 14px;border-radius:10px;background:#fff6dc;border:1px solid #f4b400;color:#0b2545;font-weight:600'; document.querySelector('.mt-actions').after(box); }
        box.textContent = text; box.scrollIntoView({behavior: 'smooth', block: 'center'});
    }
})();
</script>@endpush
