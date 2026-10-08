@php
    $user = auth()->user();
    $isOwner = $user && ($user->role_code === 2 || $user->role === 'admin');
    $isEmployer = $user && !$isOwner && ($user->role_code === 0 || $user->role === 'employer');
    // Each menu: label => [routes that mark it active, [[route, text, params, fragment], ...]]
    $menus = [
        'Jobs' => [['home', 'career-explorer', 'jobs.*', 'opportunities.*'], [
            ['home', 'Find Jobs', [], 'jobs'], ['career-explorer', 'Industry Wise Jobs'], ['jobs.hr', 'Jobs Posted by HR'],
        ]],
        'Companies' => [['sectors.*', 'companies.*', 'industrial.*', 'company.experiences.*'], [
            ['sectors.index', 'Companies by Sector'], ['sectors.index', 'Industrial Areas & Tech Parks', [], 'hubs'],
            ['companies.index', 'All Companies'], ['industrial.index', 'Industrial Area Directory'], ['company.experiences.index', 'Interview Experiences'],
        ]],
        'Learn' => [['learning.*', 'practice', 'news.*'], [
            ['learning.index', 'Learning Tracks'], ['learning.track', 'SQL Interview Prep', ['sql']], ['practice', 'Coding Practice'], ['news.index', 'Latest in Tech'],
        ]],
        'Resume' => [['resume.*'], [
            ['resume.builder', 'Resume Builder'], ['resume.builder', 'ATS-Friendly Resume', [], 'ats-preview'],
        ]],
        'Employers' => [['employer.*'], $isEmployer ? [
            ['employer.dashboard', 'HR Dashboard'], ['employer.assistant', 'Post a Job (Assistant)', ['job']], ['employer.assistant', 'Company Profile (Assistant)', ['company']], ['employer.jobs.create', 'Detailed Job Form'],
        ] : [
            ['employer.register', 'Register Your Company'], ['employer.login', 'HR Login'],
        ]],
        'About' => [['about', 'contact'], [['about', 'About Us'], ['contact', 'Contact']]],
    ];
@endphp
<style>
.sitebar[data-shared-header]{position:relative;z-index:200;background:#fff;border-bottom:1px solid #e3e9f3;box-shadow:0 4px 18px rgba(16,33,62,.06);font-family:Inter,system-ui,sans-serif}
.sitebar[data-shared-header] .sitebar-inner{width:min(1400px,calc(100% - 32px));margin:auto;display:flex;align-items:center;gap:20px;min-height:68px}
.sitebar[data-shared-header] .site-logo{flex:0 0 auto;display:block}.sitebar[data-shared-header] .site-logo img{display:block;width:128px;height:46px;object-fit:contain}
.sitebar[data-shared-header] .site-nav{display:flex;align-items:center;gap:4px;margin-left:auto;flex-wrap:wrap;justify-content:flex-end}
.sitebar[data-shared-header] .header-cta{display:inline-flex;align-items:center;padding:9px 14px;margin-right:6px;border-radius:999px;background:#2563eb;color:#fff!important;font:700 13px/1.2 Inter,system-ui,sans-serif;text-decoration:none;white-space:nowrap}
.sitebar[data-shared-header] .header-cta:hover,.sitebar[data-shared-header] .header-cta[aria-current=page]{background:#1d4ed8}
.header-group{position:relative}
.header-group>summary{list-style:none;display:flex;align-items:center;gap:7px;padding:9px 12px;border-radius:9px;color:#1e2f4d;font:600 14px/1.2 Inter,system-ui,sans-serif;cursor:pointer;white-space:nowrap;user-select:none}
.header-group>summary::-webkit-details-marker{display:none}
.header-group>summary:after{content:'';width:6px;height:6px;border-right:2px solid currentColor;border-bottom:2px solid currentColor;transform:rotate(45deg);margin-top:-3px;opacity:.6}
.header-group>summary:hover,.header-group[open]>summary{background:#eff4ff;color:#2563eb}
.header-group.is-active>summary{color:#2563eb;box-shadow:inset 0 -2px 0 #2563eb;border-radius:9px 9px 0 0}
.header-group[open]>summary:after{transform:rotate(225deg);margin-top:3px}
.header-menu{position:absolute;top:calc(100% + 8px);right:0;display:grid;min-width:240px;max-width:calc(100vw - 32px);padding:8px;background:#fff;border:1px solid #e1e8f3;border-radius:12px;box-shadow:0 18px 40px rgba(16,33,62,.16)}
.sitebar[data-shared-header] .header-menu a,.sitebar[data-shared-header] .header-menu button{display:block;text-align:left;width:100%;padding:10px 12px;border:0;border-radius:8px;background:transparent;color:#1e2f4d!important;font:500 14px/1.4 Inter,system-ui,sans-serif;text-decoration:none;box-sizing:border-box;cursor:pointer}
.sitebar[data-shared-header] .header-menu a:hover,.sitebar[data-shared-header] .header-menu button:hover{background:#eff4ff;color:#2563eb!important}
.header-menu form{margin:0}.header-menu hr{border:0;border-top:1px solid #eef2f8;margin:6px 4px}
.header-group>summary:focus-visible,.header-menu a:focus-visible,.header-menu button:focus-visible,.sitebar[data-shared-header] .header-cta:focus-visible{outline:3px solid #f59e0b;outline-offset:2px}
.header-toggle{display:none;margin-left:auto;padding:9px 12px;border:1px solid #d7e1ef;border-radius:9px;background:#fff;color:#1e2f4d;font:700 14px Inter,system-ui,sans-serif;cursor:pointer}
@media(max-width:1020px){
  .sitebar[data-shared-header] .sitebar-inner{flex-wrap:wrap;padding:10px 0;gap:10px}
  .header-toggle{display:block}
  .sitebar[data-shared-header] .site-nav{display:none;width:100%;flex-direction:column;align-items:stretch;gap:2px;padding-bottom:6px}
  .sitebar[data-shared-header].is-open .site-nav{display:flex}
  .sitebar[data-shared-header] .header-cta{justify-content:center;margin:0 0 6px}
  .header-group>summary{justify-content:space-between;padding:12px}
  .header-group.is-active>summary{box-shadow:inset 3px 0 0 #2563eb;border-radius:9px}
  .header-menu{position:static;box-shadow:none;border:0;padding:0 0 6px 12px;min-width:0}
}
</style>
<header class="sitebar" data-shared-header>
  <div class="sitebar-inner">
    <a class="site-logo" href="{{ route('home') }}" aria-label="Ascendia home"><img src="{{ asset('images/ascendia-logo.png') }}" alt="Ascendia"></a>
    <button class="header-toggle" type="button" aria-expanded="false" aria-controls="site-nav">Menu</button>
    <nav class="site-nav" id="site-nav" aria-label="Main navigation">
      <a class="header-cta" href="{{ route('sectors.index') }}" @if(request()->routeIs('sectors.*')) aria-current="page" @endif>Companies by Sector</a>
      @foreach($menus as $label => [$activeRoutes, $links])
        <details class="header-group {{ request()->routeIs(...$activeRoutes) ? 'is-active' : '' }}">
          <summary>{{ $label }}</summary>
          <div class="header-menu">
            @foreach($links as $link)
              <a href="{{ route($link[0], $link[2] ?? []) }}{{ isset($link[3]) ? '#'.$link[3] : '' }}">{{ $link[1] }}</a>
            @endforeach
          </div>
        </details>
      @endforeach
      <details class="header-group {{ request()->routeIs('dashboard', 'admin.*', 'login', 'register', 'student.*') ? 'is-active' : '' }}">
        <summary>{{ $user ? \Illuminate\Support\Str::limit(strtok($user->name, ' ') ?: 'Account', 14) : 'Account' }}</summary>
        <div class="header-menu">
          @auth
            @if($isOwner)
              <a href="{{ route('admin.dashboard') }}">Owner Dashboard</a><a href="{{ route('admin.industrial.index') }}">Industrial Manager</a><a href="{{ route('admin.job-sources') }}">Job Sources</a>
            @elseif($isEmployer)
              <a href="{{ route('employer.dashboard') }}">HR Dashboard</a>
            @else
              <a href="{{ route('dashboard') }}">My Dashboard</a><a href="{{ route('candidate.assistant') }}">Update Profile (Assistant)</a>
            @endif
            <hr><form method="post" action="{{ route('logout') }}">@csrf<button type="submit">Logout</button></form>
          @else
            <a href="{{ route('login') }}">Student Login</a><a href="{{ route('register') }}">Student Sign Up</a><hr><a href="{{ route('employer.login') }}">HR Login</a><a href="{{ route('owner.login') }}">Owner Login</a>
          @endauth
        </div>
      </details>
    </nav>
  </div>
</header>
<script>
(()=>{if(window.ascendiaHeaderBound)return;window.ascendiaHeaderBound=true;
const bar=()=>document.querySelector('[data-shared-header]');
document.addEventListener('click',event=>{
  const toggle=event.target instanceof Element?event.target.closest('.header-toggle'):null;
  if(toggle){const b=bar();const open=!b.classList.contains('is-open');b.classList.toggle('is-open',open);toggle.setAttribute('aria-expanded',String(open));return;}
  const selected=event.target instanceof Element?event.target.closest('.header-group'):null;
  document.querySelectorAll('[data-shared-header] .header-group[open]').forEach(group=>{if(group!==selected)group.open=false;});
});
document.addEventListener('keydown',event=>{if(event.key==='Escape'){document.querySelectorAll('[data-shared-header] .header-group[open]').forEach(group=>{group.open=false;group.querySelector('summary').focus();});}});
})();
</script>
