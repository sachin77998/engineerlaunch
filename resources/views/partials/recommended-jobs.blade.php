{{--
    "Recommended for you" strip. Records what the visitor searches and views (in this browser) and shows jobs ranked by
    the recommender. Optional: $viewJob (Job being viewed), $heading.
--}}
<section class="rec" data-recommendations @isset($viewJob) data-view-job="{{ $viewJob->id }}" data-view-title="{{ $viewJob->title }}" @endisset hidden>
    <div class="rec-head">
        <div><h2>{{ $heading ?? 'Recommended for you' }}</h2><p class="rec-because"></p></div>
        <button type="button" class="rec-clear" title="Forget my searches on this device">Clear history</button>
    </div>
    <div class="rec-list"></div>
</section>
@once
<style>
.rec{width:min(1250px,calc(100% - 32px));margin:18px auto;padding:18px;border:1px solid #dfe6f1;border-radius:14px;background:#fff;box-shadow:0 6px 18px rgba(11,37,69,.06);box-sizing:border-box;font-family:Inter,system-ui,sans-serif}
.rec-head{display:flex;justify-content:space-between;align-items:flex-start;gap:12px;margin-bottom:12px}
.rec-head h2{margin:0;font-size:19px;color:#0b2545}.rec-because{margin:4px 0 0;color:#5b6b82;font-size:13.5px}
.rec-clear{border:0;background:transparent;color:#5b6b82;font-size:12.5px;cursor:pointer;text-decoration:underline}
.rec-list{display:grid;grid-template-columns:repeat(auto-fill,minmax(250px,1fr));gap:12px}
.rec-card{position:relative;display:flex;flex-direction:column;gap:5px;padding:14px;border:1px solid #e2e8f2;border-radius:12px;color:#0f1c2e;text-decoration:none;background:#fbfcfe}
.rec-card:hover{border-color:#f4b400;box-shadow:0 8px 20px rgba(11,37,69,.1)}
.rec-card strong{font-size:14.5px;line-height:1.3;padding-right:52px}.rec-card span{font-size:12.5px;color:#5b6b82}
.rec-match{position:absolute;top:12px;right:12px;padding:3px 7px;border-radius:999px;background:#0b2545;color:#fff;font-size:11.5px;font-weight:800}
.rec-why{display:flex;flex-wrap:wrap;gap:4px;margin-top:2px}.rec-why i{font-style:normal;font-size:11px;padding:2px 7px;border-radius:999px;background:#fff6dc;color:#7a5600}
</style>
<script>
window.AscSignals = window.AscSignals || (() => {
    const KEY = 'ascSignals';
    const read = () => { try { return JSON.parse(localStorage.getItem(KEY) || '[]'); } catch (e) { return []; } };
    const write = list => { try { localStorage.setItem(KEY, JSON.stringify(list.slice(-40))); } catch (e) {} };
    return {
        all: read,
        add(kind, value, job) {
            value = String(value || '').trim(); if (!value || value.length > 190) return;
            const list = read().filter(s => !(s.kind === kind && s.value.toLowerCase() === value.toLowerCase()));
            list.push({kind, value, job: job || null, at: Math.floor(Date.now() / 1000)}); write(list);
        },
        clear() { write([]); },
    };
})();
document.addEventListener('DOMContentLoaded', () => {
    const q = new URLSearchParams(location.search).get('q');
    if (q) AscSignals.add('search', q);
    // Homepage / listing search boxes.
    document.querySelectorAll('form').forEach(form => form.addEventListener('submit', () => {
        const box = form.querySelector('#keyword, input[name="q"]');
        if (box && box.value.trim()) AscSignals.add('search', box.value.trim());
    }));
    const esc = v => String(v ?? '').replace(/[&<>"']/g, c => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c]));
    document.querySelectorAll('[data-recommendations]').forEach(async box => {
        const job = box.dataset.viewJob;
        if (job) AscSignals.add('view', box.dataset.viewTitle, Number(job));
        box.querySelector('.rec-clear').addEventListener('click', () => { AscSignals.clear(); box.hidden = true; });
        const params = new URLSearchParams({signals: JSON.stringify(AscSignals.all()), limit: 8});
        if (job) params.set('job', job);
        try {
            const res = await fetch('{{ route('recommendations.jobs') }}?' + params, {headers: {Accept: 'application/json'}, credentials: 'same-origin'});
            const data = await res.json();
            if (!data.jobs || !data.jobs.length) return;
            const because = (data.because || []).slice(0, 4).join(', ');
            box.querySelector('.rec-because').textContent = because ? 'Because you looked for ' + because + (data.related?.length ? ' · also similar: ' + data.related.join(', ') : '') : '';
            box.querySelector('.rec-list').innerHTML = data.jobs.map(j => `<a class="rec-card" href="${esc(j.url)}"><b class="rec-match">${j.match}%</b><strong>${esc(j.title)}</strong><span>${esc(j.company || '')}${j.location ? ' · ' + esc(j.location) : ''}${j.posted ? ' · ' + esc(j.posted) : ''}</span><div class="rec-why">${(j.reasons || []).map(r => `<i>${esc(r)}</i>`).join('')}</div></a>`).join('');
            box.hidden = false;
        } catch (e) {}
    });
});
</script>
@endonce
