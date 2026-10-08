@php
    $user = auth()->user();
    $isOwner = $user && ($user->role_code === 2 || $user->role === 'admin');
    $isEmployer = $user && !$isOwner && ($user->role_code === 0 || $user->role === 'employer');
    // Each menu: label => [routes that mark it active, [[route, text, params, fragment], ...]]
    $menus = [
        'Industries Jobs by Sector' => [['sectors.*', 'career-explorer', 'industrial.*'], [
            ['sectors.index', 'Companies by Sector'], ['sectors.index', 'Industrial Areas & Tech Parks', [], 'hubs'],
            ['industrial.index', 'Industrial Area Jobs'], ['career-explorer', 'Industry Wise Jobs & Career Paths'],
        ]],
        'Company Jobs' => [['home', 'jobs.*', 'opportunities.*', 'companies.*'], [
            ['home', 'Find Jobs', [], 'jobs'], ['companies.index', 'All Companies'], ['jobs.hr', 'Jobs Posted by HR'],
        ]],
        'Learning' => [['learning.*', 'practice'], [
            ['learning.index', 'Learning Tracks'], ['learning.track', 'SQL Interview Prep', ['sql']], ['practice', 'Coding Practice'],
        ]],
        'Resume Builder' => [['resume.*'], [
            ['resume.builder', 'Resume Builder'], ['resume.builder', 'ATS-Friendly Resume', [], 'ats-preview'],
        ]],
        'Explore' => [['company.experiences.*', 'news.*'], [
            ['company.experiences.index', 'Company Experiences'], ['news.index', 'Latest in Tech'],
        ]],
        'About' => [['about', 'contact'], [['about', 'About Us'], ['contact', 'Contact']]],
    ];
@endphp
<style>
.sitebar[data-shared-header]{position:relative;z-index:200;background:#0b2545!important;border-bottom:3px solid #f4b400;box-shadow:0 6px 20px rgba(11,37,69,.18);font-family:Inter,system-ui,sans-serif}
.sitebar[data-shared-header] .sitebar-inner{width:min(1400px,calc(100% - 32px));margin:auto;display:flex;align-items:center;gap:20px;min-height:70px}
.sitebar[data-shared-header] .site-logo{flex:0 0 auto;display:block;background:#fff;padding:4px 10px;border-radius:10px}
.sitebar[data-shared-header] .site-logo img{display:block;width:118px;height:44px;object-fit:contain}
.sitebar[data-shared-header] .site-nav{display:flex;align-items:center;gap:2px;margin-left:auto;flex-wrap:wrap;justify-content:flex-end}
.sitebar[data-shared-header] .header-group{position:relative}
.sitebar[data-shared-header] .header-group>summary{list-style:none;display:flex;align-items:center;gap:7px;padding:10px 12px;border-radius:9px;color:#e8eef8!important;background:transparent!important;border:0!important;font:600 14px/1.2 Inter,system-ui,sans-serif;cursor:pointer;white-space:nowrap;user-select:none}
.sitebar[data-shared-header] .header-group>summary::-webkit-details-marker{display:none}
.sitebar[data-shared-header] .header-group>summary:after{content:'';width:6px;height:6px;border-right:2px solid currentColor;border-bottom:2px solid currentColor;transform:rotate(45deg);margin-top:-3px;opacity:.75}
.sitebar[data-shared-header] .header-group>summary:hover,.sitebar[data-shared-header] .header-group[open]>summary{background:rgba(255,255,255,.1)!important;color:#fff!important}
.sitebar[data-shared-header] .header-group.is-active>summary{color:#f4b400!important;box-shadow:inset 0 -3px 0 #f4b400}
.sitebar[data-shared-header] .header-group.header-primary>summary{background:#f4b400!important;color:#0b2545!important;margin-right:4px}
.sitebar[data-shared-header] .header-group.header-primary>summary:hover,.sitebar[data-shared-header] .header-group.header-primary[open]>summary{background:#ffc933!important;color:#0b2545!important}
.sitebar[data-shared-header] .header-group.header-primary.is-active>summary{box-shadow:0 0 0 2px #fff inset}
.sitebar[data-shared-header] .header-group[open]>summary:after{transform:rotate(225deg);margin-top:3px}
.sitebar[data-shared-header] .header-menu{position:absolute;top:calc(100% + 10px);right:0;display:grid;min-width:250px;max-width:calc(100vw - 32px);padding:8px;background:#fff!important;border:1px solid #e2e8f2;border-top:3px solid #f4b400;border-radius:12px;box-shadow:0 18px 40px rgba(11,37,69,.22)}
.sitebar[data-shared-header] .header-menu a,.sitebar[data-shared-header] .header-menu button{display:block;text-align:left;width:100%;padding:10px 12px;border:0;border-radius:8px;background:transparent!important;color:#0f1c2e!important;font:500 14px/1.4 Inter,system-ui,sans-serif;text-decoration:none;box-sizing:border-box;cursor:pointer}
.sitebar[data-shared-header] .header-menu a:hover,.sitebar[data-shared-header] .header-menu button:hover{background:#fff6dc!important;color:#0b2545!important}
.sitebar[data-shared-header] .header-menu form{margin:0}
.sitebar[data-shared-header] .header-menu hr{border:0;border-top:1px solid #eef2f8;margin:6px 4px}
.sitebar[data-shared-header] summary:focus-visible,.sitebar[data-shared-header] .header-menu a:focus-visible,.sitebar[data-shared-header] .header-menu button:focus-visible{outline:3px solid #f4b400;outline-offset:2px}
.sitebar[data-shared-header] .header-toggle{display:none;margin-left:auto;padding:9px 14px;border:1px solid rgba(255,255,255,.35);border-radius:9px;background:transparent;color:#fff;font:700 14px Inter,system-ui,sans-serif;cursor:pointer}
@media(max-width:1180px){
  .sitebar[data-shared-header] .sitebar-inner{flex-wrap:wrap;padding:10px 0;gap:10px}
  .sitebar[data-shared-header] .header-toggle{display:block}
  .sitebar[data-shared-header] .site-nav{display:none;width:100%;flex-direction:column;align-items:stretch;gap:2px;padding-bottom:8px}
  .sitebar[data-shared-header].is-open .site-nav{display:flex}
  .sitebar[data-shared-header] .header-group>summary{justify-content:space-between;padding:12px}
  .sitebar[data-shared-header] .header-group.header-primary>summary{margin:0 0 4px}
  .sitebar[data-shared-header] .header-group.is-active>summary{box-shadow:inset 3px 0 0 #f4b400}
  .sitebar[data-shared-header] .header-menu{position:static;box-shadow:none;border:0;border-radius:10px;margin:2px 0 6px}
}
</style>
<header class="sitebar" data-shared-header>
  <div class="sitebar-inner">
    <a class="site-logo" href="{{ route('home') }}" aria-label="Ascendia home"><img src="{{ asset('images/ascendia-logo.png') }}" alt="Ascendia"></a>
    <button class="header-toggle" type="button" aria-expanded="false" aria-controls="site-nav">Menu</button>
    <nav class="site-nav" id="site-nav" aria-label="Main navigation">
      @foreach($menus as $label => [$activeRoutes, $links])
        <details class="header-group {{ $loop->index < 2 ? 'header-primary' : '' }} {{ request()->routeIs(...$activeRoutes) ? 'is-active' : '' }}">
          <summary>{{ $label }}</summary>
          <div class="header-menu">
            @foreach($links as $link)
              <a href="{{ route($link[0], $link[2] ?? []) }}{{ isset($link[3]) ? '#'.$link[3] : '' }}">{{ $link[1] }}</a>
            @endforeach
          </div>
        </details>
      @endforeach
      <details class="header-group {{ request()->routeIs('dashboard', 'admin.*', 'login', 'register', 'student.*', 'employer.*', 'candidate.*') ? 'is-active' : '' }}">
        <summary>{{ $user ? \Illuminate\Support\Str::limit(strtok($user->name, ' ') ?: 'Account', 14) : 'Account' }}</summary>
        <div class="header-menu">
          @auth
            @if($isOwner)
              <a href="{{ route('admin.dashboard') }}">Owner Dashboard</a><a href="{{ route('admin.industrial.index') }}">Industrial Manager</a><a href="{{ route('admin.job-sources') }}">Job Sources</a>
            @elseif($isEmployer)
              <a href="{{ route('employer.dashboard') }}">HR Dashboard</a><a href="{{ route('employer.assistant', 'job') }}">Post a Job (Assistant)</a><a href="{{ route('employer.assistant', 'company') }}">Company Profile (Assistant)</a><a href="{{ route('employer.jobs.create') }}">Detailed Job Form</a>
            @else
              <a href="{{ route('dashboard') }}">My Dashboard</a><a href="{{ route('candidate.assistant') }}">Update Profile (Assistant)</a>
            @endif
            <hr><form method="post" action="{{ route('logout') }}">@csrf<button type="submit">Logout</button></form>
          @else
            <a href="{{ route('login') }}">Student Login</a><a href="{{ route('register') }}">Student Sign Up</a><hr><a href="{{ route('employer.login') }}">HR Login</a><a href="{{ route('employer.register') }}">Register Your Company</a><a href="{{ route('owner.login') }}">Owner Login</a>
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
