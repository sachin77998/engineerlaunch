{{-- Homepage job assistant: asks about the visitor's skills and recommends live openings. --}}
<style>
.ja-launch{position:fixed;right:20px;bottom:20px;z-index:900;display:flex;align-items:center;gap:8px;padding:13px 18px;border:0;border-radius:999px;background:#f4b400;color:#0b2545;font:800 14px Inter,system-ui,sans-serif;box-shadow:0 10px 28px rgba(11,37,69,.35);cursor:pointer}
.ja-launch:hover{background:#ffc933}.ja-launch[hidden]{display:none}
.ja-panel{position:fixed;right:20px;bottom:20px;z-index:901;width:min(400px,calc(100vw - 24px));height:min(600px,calc(100vh - 40px));display:flex;flex-direction:column;background:#fff;border-radius:18px;box-shadow:0 24px 60px rgba(11,37,69,.35);overflow:hidden;font-family:Inter,system-ui,sans-serif}
.ja-panel[hidden]{display:none}
.ja-head{display:flex;align-items:center;gap:10px;padding:14px 16px;background:#0b2545;color:#fff;border-bottom:3px solid #f4b400}
.ja-head strong{flex:1;font-size:15px}.ja-head button{border:0;background:transparent;color:#fff;font-size:20px;cursor:pointer;line-height:1}
.ja-log{flex:1;overflow-y:auto;padding:14px;background:#f4f6fb;display:flex;flex-direction:column;gap:8px}
.ja-msg{max-width:88%;padding:10px 13px;border-radius:14px;font-size:14px;line-height:1.45;white-space:pre-wrap}
.ja-bot{background:#fff;border:1px solid #e2e8f2;border-bottom-left-radius:4px;color:#0f1c2e}
.ja-me{align-self:flex-end;background:#0b2545;color:#fff;border-bottom-right-radius:4px}
.ja-chips{display:flex;flex-wrap:wrap;gap:6px}
.ja-chips a,.ja-chips button{padding:5px 10px;border-radius:999px;border:1px solid #1d5fd1;background:#fff;color:#1d5fd1;font-size:12.5px;text-decoration:none;cursor:pointer;font-family:inherit}
.ja-chips a:hover,.ja-chips button:hover{background:#1d5fd1;color:#fff}
.ja-job{display:block;padding:9px 11px;border:1px solid #e2e8f2;border-radius:10px;background:#fff;color:#0f1c2e;text-decoration:none}
.ja-job:hover{border-color:#f4b400}.ja-job strong{display:block;font-size:13.5px}.ja-job span{font-size:12px;color:#5b6b82}
.ja-form{display:flex;gap:8px;padding:10px;border-top:1px solid #e2e8f2;background:#fff}
.ja-form input{flex:1;padding:10px 12px;border:1px solid #d7e1ef!important;border-radius:10px;font:inherit;font-size:14px;background:#fff!important;color:#0f1c2e!important}
.ja-form button{padding:10px 14px;border:0;border-radius:10px;background:#f4b400;color:#0b2545;font-weight:800;cursor:pointer}
.ja-all{display:inline-block;margin-top:2px;font-weight:700;color:#1d5fd1;font-size:13px}
</style>
<button class="ja-launch" type="button" id="ja-launch" aria-controls="ja-panel">💬 Find jobs for my skills</button>
<section class="ja-panel" id="ja-panel" role="dialog" aria-label="Job assistant" hidden>
    <header class="ja-head"><strong>Ascendia Job Assistant</strong><button type="button" id="ja-reset" title="Start over" aria-label="Start over">↺</button><button type="button" id="ja-close" aria-label="Close">×</button></header>
    <div class="ja-log" id="ja-log" aria-live="polite"></div>
    <form class="ja-form" id="ja-form"><input id="ja-input" autocomplete="off" maxlength="600" placeholder="e.g. B.Tech CSE, Java, Spring Boot" aria-label="Your answer"><button type="submit">Send</button></form>
</section>
<script>
(() => {
    const panel = document.getElementById('ja-panel'), launch = document.getElementById('ja-launch'), log = document.getElementById('ja-log');
    const form = document.getElementById('ja-form'), input = document.getElementById('ja-input');
    const esc = v => String(v ?? '').replace(/[&<>"']/g, c => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c]));
    let step = 'skills', skillsText = '', started = false;

    const say = (html, who = 'bot') => { const div = document.createElement('div'); div.className = 'ja-msg ' + (who === 'bot' ? 'ja-bot' : 'ja-me'); div.innerHTML = html; log.appendChild(div); log.scrollTop = log.scrollHeight; return div; };
    const start = () => {
        log.innerHTML = ''; step = 'skills'; skillsText = '';
        say('Hi! Tell me what you have studied or worked on, and I will show matching jobs.\n\nExamples: <em>Java, Spring Boot</em> · <em>AutoCAD, SolidWorks</em> · <em>STAAD Pro</em> · <em>PLC, SCADA</em> · <em>CNC / VMC operator</em> · <em>Tally, GST</em>');
        input.placeholder = 'e.g. B.Tech CSE, Java, Spring Boot'; input.focus();
    };
    async function recommend(location) {
        const typing = say('Looking for matching jobs…');
        try {
            const res = await fetch('{{ route('assistant.jobs') }}', {method: 'POST', headers: {'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}'}, body: JSON.stringify({text: skillsText, location})});
            const data = await res.json(); typing.remove();
            if (data.skills?.length) {
                say('I found these skills. Tap one to see all its openings:<div class="ja-chips" style="margin-top:8px">' +
                    data.skills.map(s => `<a href="${esc(s.url)}">${esc(s.skill)} · ${Number(s.count).toLocaleString()}</a>`).join('') + '</div>');
            } else {
                say('I did not recognise a specific skill, so I searched your words directly.');
            }
            if (data.jobs?.length) {
                say(`<strong>${Number(data.total).toLocaleString()} matching openings${location ? ' in ' + esc(location) : ''}.</strong> Latest:` +
                    data.jobs.map(j => `<a class="ja-job" href="${esc(j.url)}"><strong>${esc(j.title)}</strong><span>${esc(j.company || '')}${j.location ? ' · ' + esc(j.location) : ''}${j.posted ? ' · ' + esc(j.posted) : ''}${j.via ? ' · via ' + esc(j.via) : ''}</span></a>`).join('') +
                    `<a class="ja-all" href="${esc(data.search_url)}">See all ${Number(data.total).toLocaleString()} jobs →</a>`);
            } else {
                say(location ? `No openings in ${esc(location)} right now. Try another city or leave it blank.` : 'No live openings match yet. Try a related skill or a broader term.');
            }
            say('Want to refine? Type a city, or describe more skills.<div class="ja-chips" style="margin-top:8px"><button type="button" data-ja="city">Change city</button><button type="button" data-ja="skills">Different skills</button></div>');
            step = 'refine';
        } catch (e) { typing.remove(); say('Sorry, something went wrong. Please try again.'); }
    }
    form.addEventListener('submit', e => {
        e.preventDefault(); const text = input.value.trim(); if (!text) return;
        say(esc(text), 'me'); input.value = '';
        if (step === 'skills') { skillsText = text; step = 'city'; say('Great. Which city or state do you prefer? Type <em>any</em> to see all locations.'); input.placeholder = 'e.g. Pune, Ludhiana, Bengaluru or any'; return; }
        if (step === 'city' || step === 'refine-city') { recommend(/^(any|all|no|none|anywhere)$/i.test(text) ? '' : text); return; }
        // In refine mode a short single word is treated as a city, otherwise as new skills.
        if (step === 'refine') { skillsText = text; recommend(''); }
    });
    log.addEventListener('click', e => {
        const action = e.target.closest('[data-ja]')?.dataset.ja; if (!action) return;
        if (action === 'city') { step = 'refine-city'; say('Which city or state?'); input.placeholder = 'e.g. Noida'; }
        if (action === 'skills') { step = 'skills'; say('Tell me your skills or experience.'); input.placeholder = 'e.g. Python, Django'; }
        input.focus();
    });
    launch.addEventListener('click', () => { panel.hidden = false; launch.hidden = true; if (!started) { started = true; start(); } input.focus(); });
    document.getElementById('ja-close').addEventListener('click', () => { panel.hidden = true; launch.hidden = false; });
    document.getElementById('ja-reset').addEventListener('click', start);
    if (location.hash === '#assistant') launch.click();
})();
</script>
