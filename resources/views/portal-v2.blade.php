<!doctype html>
<html lang="en">
    <head>
        <meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">
        <title>EngineerLaunch — Find Your Next Opportunity</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">
<link rel="preconnect" href="https://fonts.googleapis.com"><link rel="preconnect" href="https://fonts.gstatic.com" crossorigin><link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
<style>
:root{
    --blue:#1769ff;
    --ink:#17233c;
    --muted:#697890;
    --line:#e1e7f0;
    --soft:#f7f9fc
}*{
    box-sizing:border-box
}html
{
    scroll-behavior:smooth
}
body
{
    margin:0;
    background:var(--soft);
    color:var(--ink);
    font-family:Inter,
    system-ui,
    sans-serif
}
a
{
    text-decoration:none;
    color:inherit
}button,
input,select
{
    font:inherit
}
.navbar
{
    min-height:76px;
    padding:0 max(28px,6vw);
    display:grid;
    grid-template-columns:180px minmax(0,1fr) auto;
    align-items:center;
    position:sticky;
    top:0;z-index:20;
    background:#fff;
    border-bottom:1px solid #e8edf5
}
.logo
{
  font-size:24px;
  font-weight:800;
  color:var(--ink)}
  .logo b
  {
    color:var(--blue)
  }
  .nav-links
  {
    display:flex;
    align-items:center;
    justify-content:center;
    gap:10px;
    margin:0
  }
  .nav-dropdown{position:relative}
  .nav-dropdown-toggle{display:flex;align-items:center;gap:7px;border:0;background:transparent;color:#5c6980;font:inherit;font-size:14px;font-weight:600;cursor:pointer;padding:10px 12px;border-radius:8px;transition:background .18s ease,color .18s ease}
  .nav-dropdown-toggle:hover,.nav-dropdown.open .nav-dropdown-toggle{background:#eef5ff;color:#1769ff}
  .nav-dropdown-toggle::after{content:'\25BE';font-size:12px;line-height:1;transition:transform .18s ease}
  .nav-dropdown.open .nav-dropdown-toggle::after,.nav-dropdown:focus-within .nav-dropdown-toggle::after{transform:rotate(180deg)}
  .nav-dropdown-menu{position:absolute;top:calc(100% + 9px);left:0;z-index:50;display:none;min-width:245px;padding:9px;background:#fff;border:1px solid #dbe4f0;border-radius:12px;box-shadow:0 16px 38px rgba(15,35,70,.15)}
  .nav-dropdown:hover .nav-dropdown-menu,.nav-dropdown:focus-within .nav-dropdown-menu,.nav-dropdown.open .nav-dropdown-menu{display:grid}
  .nav-dropdown-menu a{display:block!important;padding:10px 12px;border-radius:8px;color:#24344f!important}
  .nav-dropdown-menu a:hover{background:#eef5ff;color:var(--blue)!important}
  .nav-dropdown-menu small{display:block;margin-top:2px;color:#7a879c;font-size:11px;font-weight:500}
  .nav-links a,
  .nav-right a
  {
    color:#5c6980;
    font-size:14px;
    font-weight:600
}
.nav-links a:hover,
.nav-right a:hover
{
    color:var(--blue)
}
.nav-right{
    display:flex;
    align-items:center;
    gap:18px;
    margin-left:24px
}.signup{
    padding:10px 16px;
    border-radius:8px;
    border:1px solid var(--blue);
    color:var(--blue)!important
}
.hero{
    padding:62px 6%;
    background:radial-gradient(circle at 85% 20%,rgba(23,105,255,.13),transparent 35%),
    linear-gradient(135deg,#f8fbff,#eef5ff);
    border-bottom:1px solid #e6edf7
}
.hero-inner,
.container
{
    max-width:1180px;
    margin:auto
}
.hero-label
{
    display:inline-flex;
    padding:7px 13px;
    border:1px solid #dce6f5;
    border-radius:30px;
    background:#fff;
    color:var(--blue);
    font-size:12px;
    font-weight:700
}
.hero h1
{
    max-width:780px;
    margin:18px 0 14px;
    font-size:clamp(38px,5vw,56px);
    line-height:1.08;letter-spacing:-1.7px
}
.hero h1 span
{
    color:var(--blue)
}
.hero-copy
{
  max-width:680px;
  color:var(--muted);
  font-size:16px;line-height:1.7
}
.search-box
{
    display:grid;
    grid-template-columns:1fr .75fr auto;
    max-width:930px;
    margin-top:27px;
    padding:8px;
    border:1px solid #dce4ef;
    border-radius:14px;
    background:#fff;
    box-shadow:0 15px 40px rgba(28,55,95,.10)}
    .search-field
    {
        display:flex;
        align-items:center;
        gap:10px;
        padding:8px 15px;
        border-right:1px solid #e5eaf2
    }
    .search-field input
    {
        width:100%;
        padding:7px;
        border:0;
        outline:0;
        color:var(--ink)
    }
    .search-button
    {
        padding:0 26px;
        border:0;
        border-radius:10px;
        background:var(--blue);
        color:#fff;
        font-weight:700;
        cursor:pointer
    }
    .hero-stats
    {
        display:flex;
        gap:44px;
        flex-wrap:wrap;
        margin-top:28px
    }
    .stat strong
    {
        display:block;
        font-size:22px
    }
    .stat span
    {
        color:#758198;
        font-size:12px
    }
    .container
    {
        padding:44px 0 75px
    }
    .section-header,
    .job-toolbar
    {
        display:flex;
        justify-content:space-between;
        align-items:end;
        gap:20px;
        margin-bottom:20px
    }
    .section-header h2,
    .job-toolbar h2
    {
        margin:0;
        font-size:26px
    }
    .section-header p,
    .job-toolbar p
    {
        margin:5px 0 0;
        color:#7a879c;
        font-size:13px
    }
    .company-grid
    {
        display:grid;
        grid-template-columns:repeat(6,1fr);
        gap:12px
    }
    .company-card
    {
        padding:18px 10px;
        border:1px solid var(--line);
        border-radius:12px;
        background:#fff;
        text-align:center;
        cursor:pointer;transition:.2s
    }
    .company-card:hover,
    .company-card.active
    {
        transform:translateY(-3px);
        border-color:var(--blue);
        box-shadow:0 10px 25px rgba(28,55,95,.09)
    }
    .company-logo,
    .job-logo
    {
        display:grid;
        place-items:center;
        background:#f0f5ff;
        color:var(--blue);
        font-weight:800
    }
    .company-logo
    {
        width:43px;
        height:43px;
        margin:0 auto 10px;
        border-radius:10px
    }
    .company-name
    {
        font-size:12px;
        font-weight:700
    }
    .company-jobs
    {
        margin-top:4px;
        color:#8994a8;
        font-size:11px
    }
    .jobs-section
    {
        margin-top:58px
    }
    .tabs
    {
        display:flex;
        gap:8px
    }
    .tab
    {
        padding:9px 15px;
        border:1px solid #dfe6f0;
        border-radius:30px;
        background:#fff;
        color:#647189;
        font-size:12px;
        font-weight:600;
        cursor:pointer
    }
    .tab.active
    {
        background:#172b4d;
        color:#fff;
        border-color:#172b4d
    }
    .jobs-layout
    {
        display:grid;
        grid-template-columns:245px 1fr;
        gap:24px;
        align-items:start
    }
    .filters
    {
        position:sticky;
        top:92px;
        padding:20px;
        border:1px solid var(--line);
        border-radius:14px;
        background:#fff
    }
    .filters h3
    {
        margin:0 0 15px;
        font-size:15px
    }
    .filter-group
    {
        padding:14px 0;
        border-top:1px solid #edf0f5
    }
    .filter-title
    {
        margin-bottom:8px;
        font-size:12px;
        font-weight:700
    }
    .filters select
    {
        width:100%;
        padding:10px;
        border:1px solid #dfe6f0;
        border-radius:8px;
        background:#fff;
        color:#65728a;font-size:12px
    }
    .clear
    {
        width:100%;
        margin-top:8px;
        padding:10px;
        border:1px solid var(--blue);
        border-radius:8px;
        background:#fff;
        color:var(--blue);
        font-weight:700;
        cursor:pointer
    }
    .jobs-list
    {
        display:grid;
        gap:14px
    }
    .job-card
    {
        padding:22px;
        border:1px solid var(--line);
        border-radius:14px;
        background:#fff;
        transition:.2s
    }
    .job-card:hover
    {
        transform:translateY(-3px);
        border-color:#a9c6ff;
        box-shadow:0 12px 30px rgba(32,59,100,.08)
    }
    .job-top
    {
        display:flex;
        justify-content:space-between;
        gap:18px
    }
    .company-info
    {
        display:flex;
        gap:13px
    }
    .job-logo
    {
        flex:none;
        width:46px;
        height:46px;
        border-radius:10px
    }
    .company-small
    {
        margin-bottom:6px;
        color:var(--blue);
        font-size:11px;
        font-weight:800;
        text-transform:uppercase
    }
    .company-rating
    {
        margin-left:7px;
        color:#64748b;
        font-size:11px;
        font-weight:700;
        text-transform:none
    }
    .company-rating .star{color:#f59e0b}
    .job-title
    {
        margin:0 0 8px;
        font-size:18px
    }
    .job-meta
    {
        display:flex;
        gap:14px;
        flex-wrap:wrap;
        color:#76839a;
        font-size:12px
    }
    .job-description
    {
        display:-webkit-box;
        overflow:hidden;
        -webkit-line-clamp:2;
        -webkit-box-orient:vertical;
        margin:16px 0;
        color:#7b879b;
        font-size:12px;
        line-height:1.65
    }
    .job-bottom
    {
        display:flex;
        justify-content:space-between;
        align-items:center;
        gap:16px;
        padding-top:15px;
        border-top:1px solid #edf0f5
    }
    .tags
    {
        display:flex;
        gap:7px;
        flex-wrap:wrap
    }
    .tag
    {
        padding:6px 9px;
        border-radius:6px;
        background:#f4f7fb;
        color:#66738a;
        font-size:10px;
        font-weight:600
    }
    .apply
    {
        padding:9px 14px;
        border-radius:7px;
        background:var(--blue);
        color:#fff;
        font-size:11px;
        font-weight:700;
        white-space:nowrap
    }
    .empty
    {
        padding:35px;
        border:1px dashed #ccd6e5;
        border-radius:13px;
        text-align:center;
        color:#697890;
        background:#fff
    }
    .journey
    {
        margin-top:65px
    }
    .journey-grid
    {
        display:grid;
        grid-template-columns:repeat(3,1fr);
        gap:16px
    }
    .journey-card
    {
        display:block;
        padding:23px;
        border:1px solid var(--line);
        border-radius:14px;
        background:#fff;
        transition:.2s
    }
    .journey-card:hover
    {
        transform:translateY(-4px);
        border-color:var(--blue);
        box-shadow:0 15px 32px rgba(23,105,255,.10)
    }
    .journey-card b
    {
        display:grid;
        place-items:center;
        width:42px;
        height:42px;
        margin-bottom:14px;
        border-radius:10px;
        background:var(--blue);
        color:#fff
    }
    .journey-card h3
    {
        margin:0 0 8px
    }
    .journey-card p
    {
        color:var(--muted);
        font-size:13px;
        line-height:1.6
    }
    .journey-card span
    {
        color:var(--blue);
        font-size:13px;
        font-weight:700
    }
    @media(max-width:1000px)
    {
        .company-grid
        {
            grid-template-columns:repeat(3,1fr)
        }
        .jobs-layout
        {
            grid-template-columns:1fr
        }
        .filters
        {
            position:static
        }
        .nav-links
        {
            gap:14px;
            margin-left:25px
        }
    }
    @media(max-width:720px)
    {
        .navbar{padding:0 18px}
        .nav-links{display:none}
        .hero
        {
            padding:44px 20px
        }
        .search-box
        {
            grid-template-columns:1fr
        }
        .search-field
        {
            border-right:0;
            border-bottom:1px solid #e5eaf2
        }
        .search-button
        {
            height:48px
        }
        .container
        {
            padding:35px 20px
        }
        .company-grid
        {
            grid-template-columns:repeat(2,1fr)
        }
        .job-toolbar,
        .section-header,
        .job-bottom
        {
            align-items:flex-start;
            flex-direction:column
        }
        .tabs
        {
            width:100%;
            overflow:auto
        }
        .journey-grid
        {
            grid-template-columns:1fr
        }
    }
.company-logo,
.job-logo
{
    position:relative;
    overflow:hidden
}
.company-logo img,
.job-logo img
{
    width:72%;
    height:72%;
    object-fit:contain
}
.logo-fallback
{
    display:grid;
    place-items:center;
    width:100%;
    height:100%
}
.nav-right,
.nav-right a,
.signup
{
position:relative;
z-index:100;
pointer-events:auto
}
.signup
{
    display:inline-flex;
    align-items:center;
    justify-content:center;
    cursor:pointer
}
.logo img
{
    display:block;
    width:155px;
    height:58px;
    object-fit:contain
}
.candidate-nav
{
    display:flex;
    align-items:center;
    gap:8px
}
.candidate-nav img,
.candidate-nav span
{
    width:36px;
    height:36px;
    border-radius:50%;
    object-fit:cover;
    background:#1769ff;
    display:grid;
    place-items:center;
    color:#fff
}
.nav-right .btn
{
    width:132px!important;
    min-width:132px;
    height:40px;
    padding:0 10px!important;
    display:inline-flex!important;
    align-items:center;
    justify-content:center;
    color:#fff!important;
    white-space:nowrap
}
.nav-right .btn-outline-primary
{
    background:#fff!important;
    border:1px solid #dbe4fd!important;
    color:#2563eb!important;
    box-shadow:none!important
}
.nav-right .btn-outline-primary:hover
{
    background:#eff4ff!important;
    border-color:#bcd0fb!important;
    color:#1d4ed8!important
}
.search-box
{
    max-width:780px;
    padding:4px;
    border-radius:16px;
    box-shadow:0 8px 24px rgba(16,24,40,.08)
}
.search-box:focus-within
{
    box-shadow:0 8px 24px rgba(37,99,235,.15)
}
.hero-stats
{
    max-width:640px;
    gap:12px
}
.hero-stats .stat
{
    flex:1 1 150px;
    padding:14px 16px;
    border:1px solid #eef1f6;
    border-radius:12px;
    background:#fff;
    box-shadow:0 1px 2px rgba(16,24,40,.04)
}
#companies
{
    margin-top:0;
    padding:48px 28px;
    border-radius:18px;
    background:#f8faff
}
#companies .section-header
{
    align-items:flex-start
}
@media(max-width:900px)
{
    .navbar{height:auto;min-height:72px;display:flex;flex-wrap:wrap;padding:8px 20px}
    .nav-links{order:3;width:100%;margin:7px 0 0;gap:18px;overflow-x:auto;padding:8px 0}
    .nav-right{gap:8px}
    .nav-right .btn{width:auto!important;min-width:104px}
}
@media(max-width:700px)
{
    .search-box{grid-template-columns:1fr;padding:8px}
    .search-field{border-right:0;border-bottom:1px solid #e5eaf2}
    .search-button{min-height:48px}
    .hero-stats .stat{flex-basis:100%}
    #companies{padding:32px 18px}
}
.filters
{
    background:linear-gradient(180deg,#1769ff,#0f56d9);
    border-color:#1769ff;
    color:#fff;
    box-shadow:0 16px 35px rgba(23,105,255,.22)
    }
    .filters h3,
    .filters
     .filter-title
     {
        color:#fff
        }
        .filter-group
        {
            border-top-color:rgba(255,255,255,.25)
            }
            .filters select
            {
                border-color:#b9d0ff;
                color:#173460
                }
                .clear
                {
                    border-color:#fff;
                    background:#fff;
                    color:#1769ff
                    }
                    .autocomplete-field
                    {
                        position:relative
                        }
                        .suggestions
                        {
                            display:none;
                            position:absolute;
                            left:8px;
                            right:8px;
                            top:calc(100% + 10px);
                            z-index:1000;
                            max-height:290px;
                            overflow:auto;
                            border:1px solid #d8e2f0;
                            border-radius:10px;
                            background:#fff;
                            box-shadow:0 16px 35px rgba(23,42,80,.16)
                            }
                            .suggestions.open{display:block}
                            .suggestion{display:flex;justify-content:space-between;
                            gap:15px;
                            width:100%;
                            padding:11px 13px;
                            border:0;
                            border-bottom:1px solid #edf1f6;
                            background:#fff;
                            color:#17233c;
                            text-align:left;
                            cursor:pointer
                            }
                            .suggestion:hover,
                            .suggestion:focus
                            {
                                background:#eef5ff
                                }
                                .suggestion small
                                {
                                    color:#1769ff;
                                    font-weight:700
                                    }
                          </style>
                          <link rel="stylesheet" href="{{ asset('css/ascendia-dark-theme.css') }}?v=20260831-2">
                          </head>
                          <body>
<nav class="navbar">
    <a class="logo" href="/" aria-label="Ascendia home">
        <img src="{{asset('images/ascendia-logo.png')}}" alt="Ascendia — Hire today. Achieve tomorrow.">
    </a>
    <div class="nav-links">
        <div class="nav-dropdown">
            <button class="nav-dropdown-toggle" type="button" aria-expanded="false">Find Jobs</button>
            <div class="nav-dropdown-menu">
                <a href="#jobs">Find Jobs<small>Search verified openings</small></a>
                <a href="{{route('companies.index')}}">Companies<small>Browse company profiles</small></a>
                <a href="{{route('jobs.hr')}}">Jobs Posted by HR<small>Direct recruiter opportunities</small></a>
            </div>
        </div>
        <div class="nav-dropdown">
            <button class="nav-dropdown-toggle" type="button" aria-expanded="false">Learning</button>
            <div class="nav-dropdown-menu">
                <a href="{{route('learning.index')}}">Learning<small>Courses and interview preparation</small></a>
                <a href="{{route('practice')}}">Practice<small>Code and test your skills</small></a>
            </div>
        </div>
        <div class="nav-dropdown">
            <button class="nav-dropdown-toggle" type="button" aria-expanded="false">Resume Builder</button>
            <div class="nav-dropdown-menu">
                <a href="{{route('resume.builder')}}">Resume Builder<small>Create and edit your resume</small></a>
                <a href="{{route('resume.builder')}}#ats-preview">ATS-Friendly Resume<small>Preview an ATS-readable format</small></a>
            </div>
        </div>
        <a href="/about">About Us</a>
        <a href="{{route('contact')}}">Contact</a>
    </div>
    <div class="nav-right">@auth @if(auth()->user()->role_code===2||auth()->user()->role==='admin')
        <a href="/admin">
            Owner Dashboard
        </a>
        @elseif(auth()->user()->role_code===0||auth()->user()->role==='employer')
        <a href="/employer">
            HR Dashboard
        </a>
        @else @php $homeProfile=auth()->user()->candidateProfile;
         @endphp
         <a class="candidate-nav" href="/dashboard">
            @if($homeProfile?->avatar_url)
            <img src="{{$homeProfile->avatar_url}}" alt="Profile">
            @else
            <span>{{strtoupper(substr(auth()->user()->name,0,1))}}</span>
            @endif 
            <b>
                {{auth()->user()->name}}
            </b>
        </a>
        @endif 
        @else
        <a class="btn btn-outline-primary" href="{{route('employer.login')}}">
            HR Login
        </a>
        <a class="btn btn-outline-primary" href="{{route('owner.login')}}">
            Owner Login
        </a>
        <a class="btn btn-primary signup" href="{{route('register')}}">
            Student Sign Up
        </a>
        @endauth
        </div>
        </nav>
        <header class="hero">
            <div class="hero-inner">
                <div class="hero-label">✦ 
                    <span id="available-label">{{number_format($homeStats['total_jobs'])}} verified opportunities available{{--
                        Loading verified opportunities…
                    --}}</span>
                </div>
                <h1>
                    Find work that
                     <span>
                        moves you forward.
                    </span>
                </h1>
                <p class="hero-copy">
                    Discover verified job openings directly from official company career pages. 
                    Search by role, 
                    skill, or location and apply directly at the source.
                </p>
                <form class="search-box" id="search-form">
                    <label class="search-field autocomplete-field">
                        ⌕<input id="keyword" placeholder="Job Title, Skill or Company" aria-label="Job Title, Skill or Company" autocomplete="off">
                        <span class="suggestions" id="keyword-suggestions" role="listbox">

                        </span>
                    </label>
                    <label class="search-field autocomplete-field">
                    ⌖<input id="location" placeholder="City, State or Remote" aria-label="City, State or Remote" autocomplete="off">
                    <span class="suggestions" id="location-suggestions" role="listbox">

                    </span>
                </label>
                <button class="search-button">
                    Search Jobs
                </button>
            </form>
            <div class="hero-stats">
                <div class="stat">
                    <strong id="job-count">{{number_format($homeStats['total_jobs'])}}{{--
                        —
                    --}}</strong>
                    <span>
                        Active jobs
                    </span>
                </div>
                <div class="stat">
                    <strong id="company-count">{{number_format($homeStats['total_companies'])}}{{--
                        —
                    --}}</strong>
                    <span>
                        Companies hiring
                    </span>
                </div>
                <div class="stat">
                    <strong id="technology-count">{{number_format($homeStats['total_technologies'])}}{{--
                        —
                    --}}</strong>
                    <span>
                        Technology skills
                    </span>
                </div>
            </div>
        </div>
    </header>
<main class="container">
    <section id="companies">
        <div class="section-header">
            <div>
                <h2>
                    Explore top companies
                </h2>
                <p>
                    Companies with synchronized active openings
                </p>
            </div>
            <button class="tab" id="all-companies">
                All companies
            </button>
        </div>
        <div class="company-grid" id="company-grid">
            <div class="empty">
                Loading companies…
            </div>
        </div>
    </section>
<section class="jobs-section" id="jobs">
    <div class="job-toolbar">
        <div>
            <h2>
                Latest opportunities
            </h2>
            <p id="result-count">
                Loading current roles…
            </p>
        </div>
        <div class="tabs">
            <button class="tab active" data-sort="posted_at">
                Newest
            </button>
            <button class="tab" data-sort="views">
                Popular
            </button>
            <button class="tab" data-preset="graduate">
                Graduate
            </button>
            <button class="tab" data-preset="remote">
                Remote
            </button>
           </div>
        </div>
        <div class="jobs-layout">
            <aside class="filters">
                <h3>
                    Filter jobs
                </h3>
                <div class="filter-group">
                    <div class="filter-title">
                        Work mode
                    </div>
                    <select id="work-mode">
                        <option value="">Any work mode</option><option value="remote">Remote</option><option value="hybrid">Hybrid</option><option value="office">On-site</option></select></div><div class="filter-group"><div class="filter-title">Employment type</div><select id="job-type"><option value="">Any employment type</option><option>Full-time</option><option>Part-time</option><option>Contract</option><option>Internship</option></select></div><div class="filter-group"><div class="filter-title">Experience</div><select id="experience"><option value="">Any experience</option><option value="0">Fresher</option><option value="1">1 year</option><option value="3">3 years</option><option value="5">5 years</option><option value="7">7 years</option><option value="10">10+ years</option></select></div><div class="filter-group"><div class="filter-title">Posted</div><select id="posted"><option value="">Any date</option><option value="7">Last 7 days</option><option value="15">Last 15 days</option><option value="25">Last 25 days</option><option value="35">Last 35 days</option></select></div><div class="filter-group"><div class="filter-title">Role family</div><select id="role"><option value="">Any role</option><option value="software-development">Software development</option><option value="frontend">Frontend</option><option value="backend">Backend</option><option value="full-stack">Full stack</option><option value="quality-testing">Testing & QA</option><option value="devops-cloud">DevOps & cloud</option><option value="data-ai">Data & AI</option><option value="security">Security</option></select></div><div class="filter-group"><div class="filter-title">Technology</div><select id="technology"><option value="">Any technology</option></select></div><button class="clear" id="clear-filters" type="button">Clear filters</button></aside><div class="jobs-list" id="jobs-list"><div class="empty">Loading opportunities…</div></div></div></section>
      <section class="journey"><div class="section-header"><div><h2>Your complete career journey</h2><p>Everything works from this one homepage.</p></div></div><div class="journey-grid"><a class="journey-card" href="/about"><b>01</b><h3>About EngineerLaunch</h3><p>Our mission, platform model and promise to job seekers.</p><span>Read about us →</span></a><a class="journey-card" href="/learn"><b style="background:#7c3aed">02</b><h3>Learning & interview preparation</h3><p>Java, Spring Boot, Laravel, DSA, system design, ML and data science.</p><span>Start learning →</span></a><a class="journey-card" href="/practice"><b style="background:#0891b2">03</b><h3>Live practice editor</h3><p>Write and run browser-safe HTML, CSS and JavaScript.</p><span>Open editor →</span></a></div></section></main>
<script>
const state={company:'',sort:'posted_at',preset:''};
const $=s=>document.querySelector(s),esc=v=>String(v??'').replace(/[&<>'"]/g,c=>({'&':'&amp;','<':'&lt;','>':'&gt;',"'":'&#39;','"':'&quot;'}[c]));
async function api(path){const key='ascendia:api:'+path,now=Date.now();try{const saved=JSON.parse(sessionStorage.getItem(key)||'null');if(saved&&saved.expires>now)return saved.payload}catch(error){sessionStorage.removeItem(key)}const response=await fetch(path,{headers:{Accept:'application/json'},cache:'default'});if(!response.ok)throw new Error('Request failed');const payload=await response.json();try{sessionStorage.setItem(key,JSON.stringify({expires:now+3600000,payload}))}catch(error){}return payload}
function initials(name){return String(name||'CO').split(/\s+/).slice(0,2).map(x=>x[0]).join('').toUpperCase()}
function companyLogo(company){const fallback=`<span class="logo-fallback">${esc(initials(company?.name))}</span>`;if(!company?.website)return fallback;const source=`https://www.google.com/s2/favicons?domain_url=${encodeURIComponent(company.website)}&sz=128`;return `<img src="${esc(source)}" alt="${esc(company.name)} logo" loading="lazy" referrerpolicy="no-referrer" onerror="this.style.display='none';this.nextElementSibling.style.display='grid'"><span class="logo-fallback" style="display:none">${esc(initials(company.name))}</span>`}
function jobCard(job){const tags=[...(job.technologies||[]).map(x=>x.name),...(job.categories||[]).map(x=>x.name)].slice(0,5);const description=String(job.description||'Review the complete role requirements on the official company careers page.').replace(/<[^>]*>/g,' ');const isPortal=job.source==='employer'&&job.slug;const applyUrl=isPortal?`/jobs/${encodeURIComponent(job.slug)}`:(job.external_url||job.company?.careers_url||'#');const companyName=job.company?.name||'Company';const reviewCount=Number(job.company?.published_reviews_count||0);const reviewAverage=Number(job.company?.published_reviews_avg_rating||0);const rating=reviewCount?`<span class="company-rating"><span class="star">★</span> ${reviewAverage.toFixed(1)} · ${reviewCount.toLocaleString()} ${reviewCount===1?'review':'reviews'}</span>`:'';return `<article class="job-card"><div class="job-top"><div class="company-info"><div class="job-logo">${esc(initials(companyName))}</div><div><div class="company-small" data-company-name="${esc(companyName)}">${esc(companyName)}${rating}</div><h3 class="job-title">${esc(job.title)}</h3><div class="job-meta"><span>📍 ${esc(job.location||'Not specified')}</span><span>● ${esc(job.job_type||'Full-time')}</span>${job.work_mode?`<span>● ${esc(job.work_mode)}</span>`:''}</div></div></div></div><p class="job-description">${esc(description)}</p><div class="job-bottom"><div class="tags">${tags.map(tag=>`<span class="tag">${esc(tag)}</span>`).join('')}</div><a class="apply" href="${esc(applyUrl)}" ${isPortal?'':'target="_blank" rel="noopener noreferrer"'}>${isPortal?'View & apply →':'View official job →'}</a></div></article>`}
function parameters(){const p=new URLSearchParams({per_page:20,sort_by:state.sort,sort_order:'desc'});const values={q:$('#keyword').value.trim(),location:$('#location').value.trim(),company_id:state.company,work_mode:$('#work-mode').value,job_type:$('#job-type').value,experience_years:$('#experience').value,posted_within_days:$('#posted').value,role_family:$('#role').value,technology_id:$('#technology').value};if(state.preset==='graduate')values.q='graduate';if(state.preset==='remote')values.work_mode='remote';Object.entries(values).forEach(([key,value])=>{if(value!==''&&value!=null)p.set(key,value)});return p}
async function loadJobs(){const list=$('#jobs-list');list.innerHTML='<div class="empty">Loading opportunities…</div>';try{const payload=await api('/api/jobs?'+parameters());$('#result-count').textContent=`${payload.pagination.total.toLocaleString()} matching roles`;list.innerHTML=payload.data.length?payload.data.map(jobCard).join(''):'<div class="empty"><strong>No exact matches.</strong><br>Try fewer keywords or clear one of the filters.</div>'}catch(error){list.innerHTML='<div class="empty">Jobs could not be loaded. Please retry.</div>'}}
async function initialize(){const[statsResult,companiesResult,technologiesResult]=await Promise.allSettled([api('/api/jobs/stats'),api('/api/companies?per_page=100'),api('/api/technologies?per_page=100')]);if(statsResult.status==='fulfilled'){const s=statsResult.value.data;$('#job-count').textContent=Number(s.total_jobs||0).toLocaleString();$('#company-count').textContent=Number(s.total_companies||0).toLocaleString();$('#technology-count').textContent=Number(s.total_technologies||0).toLocaleString();$('#available-label').textContent=`${Number(s.total_jobs||0).toLocaleString()} verified opportunities available`}if(companiesResult.status==='fulfilled'){$('#company-grid').innerHTML=companiesResult.value.data.slice(0,12).map(company=>`<button class="company-card" data-company="${company.id}"><span class="company-logo">${esc(initials(company.name))}</span><span class="company-name">${esc(company.name)}</span><span class="company-jobs">${Number(company.active_jobs_count||0).toLocaleString()} jobs</span></button>`).join('')}else{$('#company-grid').innerHTML='<div class="empty">Company data unavailable.</div>'}if(technologiesResult.status==='fulfilled'){$('#technology').insertAdjacentHTML('beforeend',technologiesResult.value.data.map(item=>`<option value="${item.id}">${esc(item.name)}</option>`).join(''))}loadJobs()}
$('#search-form').addEventListener('submit',event=>{event.preventDefault();state.company='';state.preset='';loadJobs();$('#jobs').scrollIntoView()});$('#company-grid').addEventListener('click',event=>{const card=event.target.closest('[data-company]');if(!card)return;document.querySelectorAll('.company-card').forEach(x=>x.classList.remove('active'));card.classList.add('active');state.company=card.dataset.company;loadJobs();$('#jobs').scrollIntoView()});$('#all-companies').addEventListener('click',()=>{state.company='';document.querySelectorAll('.company-card').forEach(x=>x.classList.remove('active'));loadJobs()});document.querySelectorAll('.tab[data-sort],.tab[data-preset]').forEach(button=>button.addEventListener('click',()=>{document.querySelectorAll('.job-toolbar .tab').forEach(x=>x.classList.remove('active'));button.classList.add('active');state.sort=button.dataset.sort||'posted_at';state.preset=button.dataset.preset||'';loadJobs()}));['work-mode','job-type','experience','posted','role','technology'].forEach(id=>$('#'+id).addEventListener('change',loadJobs));$('#clear-filters').addEventListener('click',()=>{['work-mode','job-type','experience','posted','role','technology'].forEach(id=>$('#'+id).value='');$('#keyword').value='';$('#location').value='';state.company='';state.preset='';loadJobs()});initialize();
</script><script>
function attachSuggestions(inputId,type){const input=document.getElementById(inputId),box=document.getElementById(`${inputId}-suggestions`);let timer,controller;const close=()=>{box.classList.remove('open');box.innerHTML=''};input.addEventListener('input',()=>{clearTimeout(timer);controller?.abort();const q=input.value.trim();if(!q){close();return}timer=setTimeout(async()=>{controller=new AbortController();try{const response=await fetch(`/api/search/suggestions?q=${encodeURIComponent(q)}&type=${type}`,{headers:{Accept:'application/json'},signal:controller.signal});if(!response.ok)throw new Error('Suggestions unavailable');const items=(await response.json()).data||[];box.innerHTML=items.map(item=>`<button type="button" class="suggestion" data-value="${esc(item.value)}"><span>${esc(item.value)}</span><small>${esc(item.type)}</small></button>`).join('');box.classList.toggle('open',items.length>0)}catch(error){if(error.name!=='AbortError')close()}},180)});box.addEventListener('mousedown',event=>{const option=event.target.closest('[data-value]');if(!option)return;event.preventDefault();input.value=option.dataset.value;close();input.focus()});input.addEventListener('keydown',event=>{if(event.key==='Escape')close()});document.addEventListener('click',event=>{if(!event.target.closest('.autocomplete-field'))close()})}
attachSuggestions('keyword','keyword');attachSuggestions('location','location');
document.querySelectorAll('.nav-dropdown-toggle').forEach(button=>button.addEventListener('click',event=>{event.stopPropagation();const menu=button.closest('.nav-dropdown');document.querySelectorAll('.nav-dropdown.open').forEach(item=>{if(item!==menu){item.classList.remove('open');item.querySelector('.nav-dropdown-toggle')?.setAttribute('aria-expanded','false')}});const open=menu.classList.toggle('open');button.setAttribute('aria-expanded',String(open))}));document.addEventListener('click',()=>document.querySelectorAll('.nav-dropdown.open').forEach(item=>{item.classList.remove('open');item.querySelector('.nav-dropdown-toggle')?.setAttribute('aria-expanded','false')}));document.addEventListener('keydown',event=>{if(event.key==='Escape')document.dispatchEvent(new MouseEvent('click'))});
</script><script>
let companyLogoDirectory={byId:{},byName:{}};
function paintCompanyLogos(){document.querySelectorAll('.company-card[data-company]').forEach(card=>{const company=companyLogoDirectory.byId[card.dataset.company],target=card.querySelector('.company-logo');if(company&&target)target.innerHTML=companyLogo(company)});document.querySelectorAll('.job-card').forEach(card=>{const companyLabel=card.querySelector('.company-small'),name=String(companyLabel?.dataset.companyName||companyLabel?.textContent||'').trim().toLowerCase(),company=companyLogoDirectory.byName[name],target=card.querySelector('.job-logo');if(company&&target)target.innerHTML=companyLogo(company)})}
api('/api/companies?per_page=100').then(payload=>{payload.data.forEach(company=>{companyLogoDirectory.byId[String(company.id)]=company;companyLogoDirectory.byName[String(company.name).toLowerCase()]=company});paintCompanyLogos();new MutationObserver(paintCompanyLogos).observe(document.querySelector('#jobs-list'),{childList:true})}).catch(()=>{});
</script></body></html>
