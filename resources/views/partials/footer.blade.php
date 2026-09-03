<style>
.asc-site-footer{background:#f7f9fc;border-top:1px solid #e3e9f2;color:#17233c;font-family:inherit;margin-top:auto}
.asc-footer-wrap{max-width:1420px;margin:0 auto;padding:56px 32px 26px}
.asc-footer-grid{display:grid;grid-template-columns:1.25fr repeat(3,1fr) 1.45fr;gap:42px;align-items:start}
.asc-footer-brand{display:inline-flex;align-items:center;gap:11px;color:#102448;text-decoration:none;font-size:24px;font-weight:800;margin-bottom:20px}
.asc-footer-mark{display:grid;place-items:center;width:43px;height:43px;border-radius:12px;background:linear-gradient(145deg,#ffc928,#eaa400);color:#102448;font-weight:900}
.asc-footer-copy{color:#64748b;line-height:1.7;max-width:270px;margin:0 0 20px}
.asc-footer-title{font-size:13px;letter-spacing:.09em;text-transform:uppercase;color:#52647e;margin:4px 0 18px;font-weight:800}
.asc-footer-links{display:grid;gap:13px}
.asc-footer-links a,.asc-footer-legal a{color:#334963;text-decoration:none;transition:.18s ease}
.asc-footer-links a:hover,.asc-footer-legal a:hover{color:#1769ff}
.asc-footer-social{display:flex;gap:10px}
.asc-footer-social a{width:38px;height:38px;display:grid;place-items:center;border:1px solid #d7e0ec;border-radius:10px;color:#53657e;text-decoration:none;font-weight:700;background:#fff}
.asc-footer-social a:hover{border-color:#1769ff;color:#1769ff;transform:translateY(-1px)}
.asc-footer-tools{background:#fff;border:1px solid #dce4ef;border-radius:18px;padding:24px;box-shadow:0 12px 32px rgba(29,54,91,.06)}
.asc-footer-tools h3{margin:0 0 8px;font-size:20px}.asc-footer-tools p{margin:0 0 20px;color:#64748b;line-height:1.55}
.asc-footer-actions{display:flex;flex-wrap:wrap;gap:10px}.asc-footer-actions a{display:inline-flex;align-items:center;justify-content:center;padding:11px 16px;border-radius:9px;text-decoration:none;font-weight:700;border:1px solid #1769ff;color:#1769ff;background:#fff}.asc-footer-actions a:first-child{background:#1769ff;color:#fff}
.asc-footer-bottom{border-top:1px solid #dfe6ef;margin-top:40px;padding-top:22px;display:flex;justify-content:space-between;gap:25px;align-items:flex-start;color:#718096;font-size:13px;line-height:1.55}
.asc-footer-legal{display:flex;flex-wrap:wrap;gap:16px;justify-content:flex-end}
@media(max-width:1100px){.asc-footer-grid{grid-template-columns:1.2fr repeat(2,1fr)}.asc-footer-tools{grid-column:span 2}}
@media(max-width:720px){.asc-footer-wrap{padding:42px 22px 22px}.asc-footer-grid{grid-template-columns:1fr 1fr;gap:34px 24px}.asc-footer-brand-column,.asc-footer-tools{grid-column:1/-1}.asc-footer-bottom{flex-direction:column}.asc-footer-legal{justify-content:flex-start}}
@media(max-width:480px){.asc-footer-grid{grid-template-columns:1fr}.asc-footer-tools,.asc-footer-brand-column{grid-column:auto}}
</style>
<footer class="asc-site-footer" aria-label="Website footer">
    <div class="asc-footer-wrap">
        <div class="asc-footer-grid">
            <section class="asc-footer-brand-column">
                <a class="asc-footer-brand" href="{{ route('home') }}" aria-label="Ascendia home">
                    <span class="asc-footer-mark">A</span>
                    <span>{{ config('brand.name', config('platform.name', 'Ascendia')) }}</span>
                </a>
                <p class="asc-footer-copy">Verified career discovery, practical learning, ATS-ready profiles and employer hiring tools in one connected platform.</p>
                <div class="asc-footer-social" aria-label="Social links">
                    <a href="#" aria-label="LinkedIn">in</a><a href="#" aria-label="GitHub">GH</a><a href="#" aria-label="Instagram">IG</a><a href="mailto:{{ config('platform.contact.email', 'sachinsoni77998@gmail.com') }}" aria-label="Email">@</a>
                </div>
            </section>
            <section><h2 class="asc-footer-title">Company</h2><nav class="asc-footer-links"><a href="{{ route('about') }}">About us</a><a href="{{ route('about') }}#vision">Our vision</a><a href="{{ route('contact') }}">Contact us</a><a href="{{ route('companies.index') }}">Companies</a></nav></section>
            <section><h2 class="asc-footer-title">Candidates</h2><nav class="asc-footer-links"><a href="{{ route('home') }}#jobs">Find jobs</a><a href="{{ route('learning.index') }}">Learning center</a><a href="{{ route('practice') }}">Practice editor</a><a href="{{ route('student.register') }}">Create profile</a></nav></section>
            <section><h2 class="asc-footer-title">Employers & support</h2><nav class="asc-footer-links"><a href="{{ route('employer.login') }}">Employer login</a><a href="{{ route('employer.register') }}">Create employer account</a><a href="{{ route('jobs.hr') }}">HR-posted jobs</a><a href="{{ route('contact') }}">Help center</a></nav></section>
            <aside class="asc-footer-tools"><h3>Build your career on the go</h3><p>Search verified roles, prepare for interviews and keep your professional profile ready for new opportunities.</p><div class="asc-footer-actions"><a href="{{ route('home') }}#jobs">Explore jobs</a><a href="{{ route('companies.index') }}">Browse companies</a></div></aside>
        </div>
        <div class="asc-footer-bottom"><div>Copyright {{ now()->year }} Ascendia. Job details remain the property of their respective employers. Always verify information on the official application page.</div><nav class="asc-footer-legal"><a href="{{ route('contact') }}">Security</a><a href="{{ route('contact') }}">Privacy</a><a href="{{ route('contact') }}">Terms</a><a href="{{ route('contact') }}">Report an issue</a></nav></div>
    </div>
</footer>
