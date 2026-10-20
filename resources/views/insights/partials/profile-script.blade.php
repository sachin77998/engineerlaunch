<script>
(() => {
    const labels = {summary: 'Summary', 'us-market': 'US Market', 'us-financials': 'SEC Results', overview: 'Financial Highlights', growth: 'Growth Trends',
        materials: 'Raw Materials', payments: 'Payment Behaviour', concentration: 'Revenue Concentration', health: 'Financial Health Score', compliance: 'Compliance',
        'vendor-checks': 'Vendor Checks', financials: 'Financial Statements', ratios: 'Ratios', peers: 'Peer Comparison', registry: 'Registry Details', people: 'People & Team',
        ownership: 'Control & Ownership', charges: 'Charges & Borrowings', auditors: 'Auditors', business: 'Business & Related Parties', filings: 'Filings'};
    const nav = document.getElementById('pf-nav');
    const sections = [...document.querySelectorAll('main section.ci-card[id]')].filter(s => !s.closest('.pf-collapsed-parent'));
    const feedbackUrl = @json(route('insights.feedback', $company));
    const token = document.querySelector('meta[name=csrf-token]')?.content || @json(csrf_token());
    const jobs = nav.querySelector('.pf-jobs');
    sections.forEach(section => {
        const label = section.dataset.nav || labels[section.id] || section.querySelector('h2')?.textContent.trim();
        if (!label) return;
        // Menu entry.
        const a = document.createElement('a'); a.href = '#' + section.id; a.textContent = label; nav.insertBefore(a, jobs);
        // Copy link and collapse.
        const h2 = section.querySelector('h2');
        if (h2 && !section.querySelector('.pf-tools')) {
            const tools = document.createElement('div'); tools.className = 'pf-tools';
            tools.innerHTML = '<button type="button" title="Copy link to this section" aria-label="Copy link">🔗</button><button type="button" title="Collapse" aria-label="Collapse section">⌄</button>';
            h2.before(tools);
            tools.children[0].addEventListener('click', () => { navigator.clipboard?.writeText(location.href.split('#')[0] + '#' + section.id); tools.children[0].textContent = '✓'; setTimeout(() => tools.children[0].textContent = '🔗', 1200); });
            tools.children[1].addEventListener('click', () => { section.classList.toggle('pf-collapsed'); tools.children[1].textContent = section.classList.contains('pf-collapsed') ? '›' : '⌄'; });
        }
        // Was this section helpful?
        if (!section.classList.contains('ci-locked')) {
            const help = document.createElement('div'); help.className = 'pf-help';
            help.innerHTML = `<span>Is the ${label} section helpful?</span><button type="button" data-v="1">👍 Yes</button><button type="button" data-v="0">👎 No</button>`;
            help.querySelectorAll('button').forEach(b => b.addEventListener('click', () => {
                help.querySelectorAll('button').forEach(x => x.classList.toggle('on', x === b));
                fetch(feedbackUrl, {method: 'POST', headers: {'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': token}, body: JSON.stringify({section: section.id, helpful: b.dataset.v === '1'})})
                    .then(() => { help.querySelector('span').textContent = 'Thanks for the feedback.'; }).catch(() => {});
            }));
            section.appendChild(help);
        }
    });
    // Highlight the section in view.
    const links = Object.fromEntries([...nav.querySelectorAll('a[href^="#"]')].map(a => [a.getAttribute('href').slice(1), a]));
    if ('IntersectionObserver' in window) {
        const io = new IntersectionObserver(entries => entries.forEach(e => {
            if (!e.isIntersecting) return;
            Object.values(links).forEach(l => l.classList.remove('active'));
            links[e.target.id]?.classList.add('active');
        }), {rootMargin: '-15% 0px -70% 0px'});
        sections.forEach(s => io.observe(s));
    }
    // Compact header once the profile card scrolls away.
    const sticky = document.getElementById('pf-sticky'), head = document.querySelector('.pf-head');
    if (sticky && head && 'IntersectionObserver' in window) new IntersectionObserver(([e]) => sticky.classList.toggle('on', !e.isIntersecting)).observe(head);
    // People & Team: All / Current / Past and search.
    const people = document.getElementById('pf-people');
    if (people) {
        let mode = 'all';
        const apply = () => { const q = (people.querySelector('input')?.value || '').toLowerCase();
            people.querySelectorAll('tbody tr').forEach(r => { r.hidden = (mode !== 'all' && r.dataset.status !== mode) || (q && !r.textContent.toLowerCase().includes(q)); }); };
        people.querySelectorAll('[data-mode]').forEach(b => b.addEventListener('click', () => { mode = b.dataset.mode; people.querySelectorAll('[data-mode]').forEach(x => x.classList.toggle('on', x === b)); apply(); }));
        people.querySelector('input')?.addEventListener('input', apply);
    }
})();
</script>
