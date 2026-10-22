<div id="aw" class="aw" data-endpoint="{{ route('assistant.chat') }}" data-token="{{ csrf_token() }}">
    <button type="button" class="aw-fab" id="aw-open" aria-label="Open assistant" aria-expanded="false">
        <svg width="26" height="26" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M21 12a8 8 0 0 1-11.6 7.1L4 20l1-4.6A8 8 0 1 1 21 12Z"/><path d="M8.5 11h.01M12 11h.01M15.5 11h.01"/></svg>
    </button>
    <section class="aw-panel" id="aw-panel" role="dialog" aria-label="Assistant" hidden>
        <header class="aw-head"><div><b>Ask Ascendia</b><small>Jobs · companies · tax · funds · retirement</small></div><button type="button" id="aw-close" aria-label="Close assistant">✕</button></header>
        <div class="aw-log" id="aw-log" aria-live="polite">
            <div class="aw-msg bot">Hi! Ask me anything — for example:<div class="aw-chips">
                <button type="button">Salary 39000, 5000 in MF, 2000 in policy, 1000 FD, 1000 RD. Retire at 60 with 1.5 lakh a month?</button>
                <button type="button">I earned 50 lakh from mutual funds over 50 years. How do I save tax with my wife and children?</button>
                <button type="button">Which pension plan gives monthly income after retirement?</button>
                <button type="button">Find Laravel developer jobs in Pune</button>
            </div></div>
        </div>
        <form class="aw-form" id="aw-form"><textarea id="aw-input" rows="1" maxlength="2000" placeholder="Type your question…" aria-label="Your question" required></textarea><button type="submit" aria-label="Send">➤</button></form>
    </section>
</div>
<style>
.aw{--aw-bg:#fff;--aw-ink:#10213e;--aw-soft:#f1f5fb;--aw-line:#e1e8f2;position:fixed;right:20px;bottom:20px;z-index:9999;font-family:Inter,system-ui,sans-serif}
.aw-fab{width:58px;height:58px;border-radius:50%;border:0;background:linear-gradient(135deg,#1d4ed8,#2563eb);color:#fff;display:grid;place-items:center;cursor:pointer;box-shadow:0 12px 30px rgba(37,99,235,.35);transition:transform .15s}
.aw-fab:hover{transform:scale(1.06)}
.aw-panel{position:absolute;right:0;bottom:72px;width:min(400px,calc(100vw - 32px));height:min(600px,calc(100vh - 120px));background:var(--aw-bg);color:var(--aw-ink);border:1px solid var(--aw-line);border-radius:18px;box-shadow:0 24px 60px rgba(16,33,62,.25);display:flex;flex-direction:column;overflow:hidden}
.aw-panel[hidden]{display:none}
.aw-head{display:flex;justify-content:space-between;align-items:center;padding:14px 16px;background:linear-gradient(135deg,#10213e,#1d4ed8);color:#fff}.aw-head small{display:block;opacity:.8;font-size:12px}.aw-head button{background:transparent;border:0;color:#fff;font-size:18px;cursor:pointer}
.aw-log{flex:1;overflow-y:auto;padding:14px;display:flex;flex-direction:column;gap:10px;background:var(--aw-soft)}
.aw-msg{max-width:88%;padding:10px 13px;border-radius:14px;font-size:14px;line-height:1.5;white-space:pre-wrap;word-wrap:break-word}
.aw-msg.bot{background:var(--aw-bg);border:1px solid var(--aw-line);align-self:flex-start;border-bottom-left-radius:4px}.aw-msg.me{background:#2563eb;color:#fff;align-self:flex-end;border-bottom-right-radius:4px}
.aw-msg a.aw-link{display:inline-block;margin:8px 6px 0 0;padding:5px 10px;border-radius:999px;background:#eef4ff;color:#1d4ed8;font-weight:650;font-size:12px;text-decoration:none;white-space:nowrap}
.aw-chips{display:flex;flex-direction:column;gap:6px;margin-top:8px}.aw-chips button{text-align:left;border:1px solid var(--aw-line);background:var(--aw-soft);border-radius:10px;padding:8px 10px;font:inherit;font-size:13px;cursor:pointer;color:inherit}.aw-chips button:hover{border-color:#93c5fd}
.aw-typing{display:inline-flex;gap:4px}.aw-typing i{width:7px;height:7px;border-radius:50%;background:#94a3b8;animation:aw 1s infinite}.aw-typing i:nth-child(2){animation-delay:.15s}.aw-typing i:nth-child(3){animation-delay:.3s}@keyframes aw{50%{opacity:.3;transform:translateY(-3px)}}
.aw-form{display:flex;gap:8px;padding:10px;border-top:1px solid var(--aw-line);background:var(--aw-bg)}.aw-form textarea{flex:1;resize:none;border:1px solid var(--aw-line);border-radius:12px;padding:10px 12px;font:inherit;font-size:14px;max-height:120px;background:var(--aw-bg);color:inherit}
.aw-form button{width:44px;border:0;border-radius:12px;background:#2563eb;color:#fff;font-size:18px;cursor:pointer}.aw-form button:disabled{opacity:.5}
@media (prefers-color-scheme: dark){:root:not([data-theme="light"]) .aw{--aw-bg:#0f1b31;--aw-ink:#e6edf8;--aw-soft:#0b1526;--aw-line:#22324f}}
:root[data-theme="dark"] .aw{--aw-bg:#0f1b31;--aw-ink:#e6edf8;--aw-soft:#0b1526;--aw-line:#22324f}
@media(max-width:520px){.aw{right:12px;bottom:12px}.aw-panel{bottom:68px}}
</style>
<script>
(() => {
  const root = document.getElementById('aw'); if (!root || root.dataset.ready) return; root.dataset.ready = 1;
  const panel = document.getElementById('aw-panel'), log = document.getElementById('aw-log'), form = document.getElementById('aw-form'), input = document.getElementById('aw-input'), fab = document.getElementById('aw-open');
  let cid = null; try { cid = sessionStorage.getItem('aw-cid'); } catch (e) {}
  const toggle = open => { panel.hidden = !open; fab.setAttribute('aria-expanded', open); if (open) input.focus(); };
  fab.addEventListener('click', () => toggle(panel.hidden));
  document.getElementById('aw-close').addEventListener('click', () => toggle(false));
  const add = (text, who, links = []) => {
    const d = document.createElement('div'); d.className = 'aw-msg ' + who; d.textContent = text;
    links.forEach(([label, url]) => { const a = document.createElement('a'); a.className = 'aw-link'; a.href = url; a.textContent = label + ' →'; d.appendChild(a); });
    log.appendChild(d); log.scrollTop = log.scrollHeight; return d;
  };
  const send = async text => {
    add(text, 'me');
    const wait = add('', 'bot'); wait.innerHTML = '<span class="aw-typing"><i></i><i></i><i></i></span>';
    form.querySelector('button').disabled = true;
    try {
      const r = await fetch(root.dataset.endpoint, { method: 'POST', credentials: 'same-origin', headers: { 'Content-Type': 'application/json', Accept: 'application/json', 'X-CSRF-TOKEN': root.dataset.token },
        body: JSON.stringify({ message: text, conversation_id: cid ? Number(cid) : null, page: location.pathname }) });
      const j = await r.json().catch(() => ({}));
      wait.remove();
      add(r.status === 429 ? 'You are sending messages quickly — please wait a minute.' : (j.message || 'Something went wrong. Please try again.'), 'bot', j.links || []);
      if (j.conversation_id) { cid = j.conversation_id; try { sessionStorage.setItem('aw-cid', cid); } catch (e) {} }
    } catch (e) { wait.remove(); add('Network error — please check your connection.', 'bot'); }
    form.querySelector('button').disabled = false; input.focus();
  };
  form.addEventListener('submit', e => { e.preventDefault(); const t = input.value.trim(); if (!t) return; input.value = ''; send(t); });
  input.addEventListener('keydown', e => { if (e.key === 'Enter' && !e.shiftKey) { e.preventDefault(); form.requestSubmit(); } });
  log.addEventListener('click', e => { if (e.target.matches('.aw-chips button')) send(e.target.textContent); });
})();
</script>
