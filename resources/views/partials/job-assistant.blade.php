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
    <form class="ja-form" id="ja-form"><input id="ja-input" autocomplete="off" maxlength="600" placeholder="e.g. B.Tech CSE" aria-label="Your answer"><button type="submit">Send</button></form>
</section>
<script>
(() => {
    const panel = document.getElementById('ja-panel'), launch = document.getElementById('ja-launch'), log = document.getElementById('ja-log');
    const form = document.getElementById('ja-form'), input = document.getElementById('ja-input');
    const esc = v => String(v ?? '').replace(/[&<>"']/g, c => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c]));
    const thisYear = new Date().getFullYear();
    let step = 'education', profile = {}, started = false;

    const say = (html, who = 'bot') => { const div = document.createElement('div'); div.className = 'ja-msg ' + (who === 'bot' ? 'ja-bot' : 'ja-me'); div.innerHTML = html; log.appendChild(div); log.scrollTop = log.scrollHeight; return div; };
    const chips = list => '<div class="ja-chips" style="margin-top:8px">' + list.map(([label, value]) => `<button type="button" data-answer="${esc(value)}">${esc(label)}</button>`).join('') + '</div>';
    const ask = (html, placeholder, options) => { say(html + (options ? chips(options) : '')); input.placeholder = placeholder; input.focus(); };

    const QUESTIONS = {
        education: () => ask('Hi! I will match you with live openings.\n\n<strong>What did you study?</strong>', 'e.g. B.Tech CSE, Diploma Mechanical, B.Com',
            [['B.Tech / B.E. CSE', 'B.Tech CSE'], ['B.Tech Mechanical', 'B.Tech Mechanical'], ['B.Tech ECE / EEE', 'B.Tech ECE'], ['Diploma / ITI', 'Diploma'], ['B.Com / MBA', 'B.Com'], ['Skip', '-']]),
        passout: () => ask('<strong>Which year did you pass out?</strong> (your batch)', 'e.g. 2022',
            [[String(thisYear), thisYear], [String(thisYear - 1), thisYear - 1], [String(thisYear - 2), thisYear - 2], [String(thisYear - 4), thisYear - 4], ['Still studying', thisYear + 1], ['Skip', '-']]),
        experience: () => {
            const guess = profile.passout_year ? Math.max(0, thisYear - profile.passout_year) : null;
            ask('<strong>How many years of work experience do you have?</strong>' + (guess !== null ? `\nThe ${profile.passout_year} batch is about ${guess} year(s) out of college.` : ''), 'e.g. 0 for fresher, 3',
                [['Fresher', 0], ['1 year', 1], ['2 years', 2], ['3 years', 3], ['4-5 years', 4], ['6+ years', 6]]);
        },
        skills: () => ask('<strong>Which skills do you have?</strong> Languages, tools or machines you work with.\n\nExamples: <em>PHP, Laravel</em> · <em>Java, Spring Boot</em> · <em>C++</em> · <em>AutoCAD, SolidWorks</em> · <em>CNC / VMC operator</em> · <em>Tally, GST</em>', 'e.g. PHP, Laravel, MySQL'),
        city: () => ask('<strong>Which city or state do you prefer?</strong>', 'e.g. Pune, Noida or any', [['Anywhere', 'any'], ['Remote', 'remote'], ['Bengaluru', 'Bengaluru'], ['Pune', 'Pune'], ['Gurugram', 'Gurugram']]),
    };
    const ORDER = ['education', 'passout', 'experience', 'skills', 'city'];

    const start = () => { log.innerHTML = ''; profile = {}; step = 'education'; QUESTIONS.education(); };
    const next = () => { step = ORDER[ORDER.indexOf(step) + 1] || 'refine'; if (QUESTIONS[step]) QUESTIONS[step](); else recommend(); };

    function answer(text) {
        const skip = text === '-' || /^skip$/i.test(text);
        if (step === 'education') { if (!skip) profile.education = text; }
        else if (step === 'passout') { const year = parseInt(text, 10); if (!skip && year > 1970 && year < 2040) profile.passout_year = year; }
        else if (step === 'experience') { const years = /fresher/i.test(text) ? 0 : parseInt(text, 10); if (!isNaN(years)) profile.experience = years; }
        else if (step === 'skills') { profile.text = text; }
        else if (step === 'city') { profile.location = /^(any|all|no|none|anywhere)$/i.test(text) ? '' : text; }
        else if (step === 'refine') { profile.text = text; recommend(); return; }
        else if (step === 'refine-city') { profile.location = /^(any|all|no|none|anywhere)$/i.test(text) ? '' : text; recommend(); return; }
        next();
    }

    const jobCard = j => `<a class="ja-job" href="${esc(j.url)}"><strong>${j.match ? `<span style="float:right;color:#0b2545">${j.match}%</span>` : ''}${esc(j.title)}</strong><span>${esc(j.company || '')}${j.location ? ' · ' + esc(j.location) : ''}${j.posted ? ' · ' + esc(j.posted) : ''}${j.via ? ' · via ' + esc(j.via) : ''}</span>${j.reasons?.length ? `<span style="display:block;color:#7a5600">${j.reasons.map(esc).join(' · ')}</span>` : ''}</a>`;

    async function recommend() {
        step = 'refine';
        const typing = say('Matching your profile with live openings…');
        const body = {text: profile.text || profile.education || 'jobs', location: profile.location || '', education: profile.education || null, passout_year: profile.passout_year || null, experience: profile.experience ?? null};
        try {
            const res = await fetch('{{ route('assistant.jobs') }}', {method: 'POST', headers: {'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}'}, body: JSON.stringify(body)});
            const data = await res.json(); typing.remove();
            if (window.AscSignals && profile.text) AscSignals.add('assistant', profile.text);
            if (data.career_note) say('🎓 ' + esc(data.career_note));
            if (data.skills?.length) {
                say('Skills I found (tap to see every opening):' + '<div class="ja-chips" style="margin-top:8px">' +
                    data.skills.map(s => `<a href="${esc(s.url)}">${esc(s.skill)} · ${Number(s.count).toLocaleString()}</a>`).join('') + '</div>' +
                    (data.related_skills?.length ? `<div style="margin-top:6px;font-size:12.5px;color:#5b6b82">Similar skills employers ask for: ${data.related_skills.map(esc).join(', ')}</div>` : ''));
            }
            const best = data.recommended?.length ? data.recommended : data.jobs;
            if (best?.length) {
                say(`<strong>Best matches for you${body.location ? ' in ' + esc(body.location) : ''}</strong> (ranked by skills, experience and freshness):` + best.map(jobCard).join('') +
                    (data.total ? `<a class="ja-all" href="${esc(data.search_url)}">See all ${Number(data.total).toLocaleString()} matching jobs →</a>` : ''));
            } else {
                say(body.location ? `No openings in ${esc(body.location)} right now. Try another city or anywhere.` : 'No live openings match yet. Try a related skill or a broader term.');
            }
            say('Refine: type more skills, or' + chips([['Change city', '#city'], ['Change experience', '#experience'], ['Start over', '#restart']]));
        } catch (e) { typing.remove(); say('Sorry, something went wrong. Please try again.'); }
    }

    form.addEventListener('submit', e => {
        e.preventDefault(); const text = input.value.trim(); if (!text) return;
        say(esc(text), 'me'); input.value = ''; answer(text);
    });
    log.addEventListener('click', e => {
        const button = e.target.closest('[data-answer]'); if (!button) return;
        const value = button.dataset.answer;
        if (value === '#city') { step = 'refine-city'; ask('Which city or state?', 'e.g. Noida'); return; }
        if (value === '#experience') { step = 'experience'; QUESTIONS.experience(); ORDER.splice(0); ORDER.push('experience', 'refine'); return; }
        if (value === '#restart') { ORDER.splice(0, ORDER.length, 'education', 'passout', 'experience', 'skills', 'city'); start(); return; }
        say(esc(button.textContent), 'me'); answer(value);
    });
    launch.addEventListener('click', () => { panel.hidden = false; launch.hidden = true; if (!started) { started = true; start(); } input.focus(); });
    document.getElementById('ja-close').addEventListener('click', () => { panel.hidden = true; launch.hidden = false; });
    document.getElementById('ja-reset').addEventListener('click', () => { ORDER.splice(0, ORDER.length, 'education', 'passout', 'experience', 'skills', 'city'); start(); });
    if (location.hash === '#assistant') launch.click();
})();
</script>
