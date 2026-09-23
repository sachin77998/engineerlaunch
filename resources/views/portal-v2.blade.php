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
  .nav-links .latest-tech-link{display:inline-flex;align-items:center;justify-content:center;padding:10px 14px;border:1px solid #6ea2e6;border-radius:8px;background:#176fe5;color:#fff!important;font-weight:800;white-space:nowrap}
  .nav-links .latest-tech-link:hover{background:#0f5fc9;color:#fff!important}
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
    max-width:820px;
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
<link rel="stylesheet" href="{{ asset('css/ascendia-dark-theme.css') }}?v=20260907-1">
<style>
.navbar .nav-links,.navbar .nav-right{gap:10px!important}.navbar .nav-right{margin-left:10px!important}.navbar .nav-links>.nav-dropdown>.nav-dropdown-toggle,.navbar .nav-links>a,.navbar .nav-right>a{display:inline-flex!important;align-items:center!important;justify-content:center!important;min-width:112px!important;width:auto!important;height:44px!important;padding:0 15px!important;border:1px solid #7095c2!important;border-radius:9px!important;background:#315f96!important;color:#fff!important;font:750 14px/1 Inter,system-ui,sans-serif!important;text-decoration:none!important;white-space:nowrap!important;box-shadow:0 4px 12px rgba(8,28,54,.12)!important;cursor:pointer!important;transition:background-color .18s ease,border-color .18s ease,transform .18s ease,box-shadow .18s ease!important}.navbar .nav-links>.nav-dropdown>.nav-dropdown-toggle:hover,.navbar .nav-links>.nav-dropdown.open>.nav-dropdown-toggle,.navbar .nav-links>a:hover,.navbar .nav-right>a:hover{border-color:#9fc1e8!important;background:#3f73ad!important;color:#fff!important;transform:translateY(-1px)!important;box-shadow:0 7px 16px rgba(8,28,54,.2)!important}.navbar .nav-links>.nav-dropdown>.nav-dropdown-toggle:focus-visible,.navbar .nav-links>a:focus-visible,.navbar .nav-right>a:focus-visible{outline:3px solid rgba(159,193,232,.42)!important;outline-offset:3px!important}.navbar .nav-dropdown-toggle::after{margin-left:7px}@media(max-width:900px){.navbar .nav-links>.nav-dropdown,.navbar .nav-links>a{flex:1 0 135px}.navbar .nav-links>.nav-dropdown>.nav-dropdown-toggle,.navbar .nav-links>a{width:100%!important}}
.search-field input::placeholder{color:#9fb0c8;opacity:1;font-size:15px;font-weight:500}.search-field input{font-size:15px!important;font-weight:500}.search-button{height:58px!important;padding:0 30px!important;border-radius:12px!important;background:#4f7fd8!important;font-size:16px!important;transition:transform .2s ease,filter .2s ease}.search-button:hover{transform:translateY(-1px);filter:brightness(1.08)}
.navbar{padding:12px 24px!important;gap:14px!important}.navbar .logo{flex:0 0 auto!important}.navbar .nav-links{min-width:0!important;flex:1 1 auto!important;gap:7px!important}.navbar .nav-right{flex:0 0 auto!important;gap:7px!important;margin-left:0!important}@media(max-width:1550px){.navbar .nav-links>.nav-dropdown>.nav-dropdown-toggle,.navbar .nav-links>a,.navbar .nav-right>a{min-width:0!important;padding:0 11px!important;font-size:13px!important}}@media(max-width:1120px){.navbar{align-items:flex-start!important;flex-wrap:wrap!important}.navbar .nav-links{order:3!important;width:100%!important;overflow-x:auto!important;padding:7px 0!important}.navbar .nav-right{margin-left:auto!important}}
</style>
                          </head>
                          <body>
@include('partials.shared-header')
        <header class="hero">
            <div class="hero-inner">
                <div class="hero-label">✦ 
                    <span id="available-label">{{$homeStats['total_jobs'] > 0 ? number_format($homeStats['total_jobs']).' verified opportunities' : 'No verified opportunities available'}}{{--
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
                    Discover verified job openings from official company career pages.
                    Search by role, skill or location and apply directly through the company.
                </p>
                <form class="search-box" id="search-form">
                    <label class="search-field autocomplete-field">
                        ⌕<input id="keyword" name="keyword" placeholder="Search job title, skill or company" aria-label="Search by job title, skill or company" autocomplete="off">
                        <span class="suggestions" id="keyword-suggestions" role="listbox">

                        </span>
                    </label>
                    <label class="search-field autocomplete-field">
                    ⌖<input id="location" name="location" placeholder="Location, city, state or remote" aria-label="Search by location, city, state or remote" autocomplete="off">
                    <span class="suggestions" id="location-suggestions" role="listbox">

                    </span>
                </label>
                <button class="search-button" type="submit">
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
                        Companies listed
                    </span>
                </div>
                <div class="stat">
                    <strong id="hiring-company-count">{{number_format($homeStats['hiring_companies'])}}</strong>
                    <span>
                        Verified hiring feeds
                    </span>
                </div>
                <div class="stat">
                    <strong id="technology-count">{{number_format($homeStats['total_technologies'])}}{{--
                        —
                    --}}</strong>
                    <span>
                        Searchable skills
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
                <div class="filter-group"><label class="filter-title" for="geo-region">Region</label><select id="geo-region"><option value="">All regions</option></select></div>
                <div class="filter-group"><label class="filter-title" for="geo-country">Country</label><select id="geo-country"><option value="">All countries</option></select></div>
                <div class="filter-group"><label class="filter-title" for="geo-state">State / Province</label><select id="geo-state" disabled><option value="">Select a country first</option></select></div>
                <div class="filter-group"><label class="filter-title" for="geo-city">City</label><input id="geo-city" list="geo-cities" placeholder="Choose country, then type city" maxlength="150" disabled autocomplete="off"><datalist id="geo-cities"></datalist><small id="geo-status" role="status">Choose a country to browse states and cities.</small><small><a href="https://github.com/dr5hn/countries-states-cities-database" target="_blank" rel="noopener noreferrer" style="color:inherit">Location reference: CSC (ODbL)</a></small></div>
                <style>.filters .filter-title{display:block}.filters #geo-city{box-sizing:border-box;width:100%;min-width:0;padding:14px 12px;border:1px solid #bccfe3;border-radius:10px;background:white;color:#213d5c;font:inherit}.filters select:disabled,.filters input:disabled{opacity:.65}.filters #geo-status{display:block;line-height:1.5;margin-top:10px;color:#fff}.filters select{max-width:100%}</style>
                <div class="filter-group">
                    <div class="filter-title">
                        Work mode
                    </div>
                    <select id="work-mode">
                        <option value="">Any work mode</option><option value="remote">Remote</option><option value="hybrid">Hybrid</option><option value="office">On-site</option></select></div><div class="filter-group"><div class="filter-title">Employment type</div><select id="job-type"><option value="">Any employment type</option><option>Full-time</option><option>Part-time</option><option>Contract</option><option>Internship</option></select></div><div class="filter-group"><div class="filter-title">Experience</div><select id="experience"><option value="">Any experience</option><option value="0">Fresher</option><option value="1">1 year</option><option value="3">3 years</option><option value="5">5 years</option><option value="7">7 years</option><option value="10">10+ years</option></select></div><div class="filter-group"><div class="filter-title">Posted</div><select id="posted"><option value="">Any date</option><option value="7">Last 7 days</option><option value="15">Last 15 days</option><option value="25">Last 25 days</option><option value="35">Last 35 days</option></select></div><div class="filter-group"><div class="filter-title">Role family</div><select id="role"><option value="">Any role</option><option value="software-development">Software development</option><option value="frontend">Frontend</option><option value="backend">Backend</option><option value="full-stack">Full stack</option><option value="quality-testing">Testing & QA</option><option value="devops-cloud">DevOps & cloud</option><option value="data-ai">Data & AI</option><option value="security">Security</option></select></div><div class="filter-group"><div class="filter-title">Technology</div><select id="technology"><option value="">Any technology</option></select></div><button class="clear" id="clear-filters" type="button">Clear filters</button></aside><div class="jobs-list" id="jobs-list"><div class="empty">Loading opportunities…</div></div></div></section>
<style>.why-ascendia{margin:70px calc(50% - 50vw) 0;padding:80px max(24px,calc((100vw - 1440px)/2));background:#f8fafc}.why-head{max-width:820px;margin:0 auto 40px;text-align:center}.why-label{display:inline-block;padding:7px 14px;border-radius:50px;background:#eaf2ff;color:#2563eb;font-size:12px;font-weight:800;letter-spacing:1px}.why-head h2{margin:18px 0 12px;color:#0f172a;font-size:clamp(30px,4vw,42px)}.why-head p{color:#64748b;font-size:17px;line-height:1.7}.comparison-wrap{overflow-x:auto;border-radius:20px;background:#fff;box-shadow:0 20px 60px #0f172a14}.comparison{width:100%;min-width:920px;border-collapse:collapse}.comparison th,.comparison td{padding:17px 20px;text-align:left;border-top:1px solid #e5e7eb;color:#475569}.comparison th{border:0;background:#f8fafc;color:#334155}.comparison td:first-child{color:#0f172a;font-weight:750}.comparison .asc{background:#eff6ff;color:#2563eb;font-weight:800}.comparison .yes{color:#16a34a;font-size:19px}.employer-value{display:grid;grid-template-columns:1fr 1fr;gap:45px;align-items:center;margin:70px 0}.employer-copy h2{font-size:clamp(28px,4vw,40px);color:#0f172a}.employer-copy p{color:#64748b;line-height:1.75}.workflow-card{padding:25px;border:1px solid #dbe7f8;border-radius:20px;background:#fff}.workflow-step{display:flex;align-items:center;gap:14px;padding:13px;border-radius:11px;color:#334155;font-weight:750}.workflow-step span{display:grid;width:38px;height:38px;place-items:center;border-radius:10px;background:#eaf2ff;color:#2563eb}.workflow-step.active{background:#eff6ff;color:#1d4ed8}.workflow-line{height:12px;margin-left:31px;border-left:2px solid #bfdbfe}.usp{margin:45px 0;padding:35px;border-radius:22px;background:linear-gradient(135deg,#102544,#214d82);color:#fff;text-align:center}.usp h2{font-size:clamp(26px,4vw,38px)}.usp-flow{display:flex;justify-content:center;gap:9px;flex-wrap:wrap;margin-top:24px}.usp-flow span{padding:10px 13px;border:1px solid #ffffff2d;border-radius:9px;background:#ffffff12}.usp-flow b{align-self:center;color:#93c5fd}@media(max-width:800px){.employer-value{grid-template-columns:1fr}.why-ascendia{padding-top:55px}}</style>
<section class="why-ascendia"><div class="why-head"><span class="why-label">WHY ASCENDIA?</span><h2>More than a job portal. A complete career and workforce platform.</h2><p>Find opportunities, build skills, understand the market, get hired and manage the employee journey — all from one platform.</p></div><div class="comparison-wrap"><table class="comparison"><thead><tr><th>Capability</th><th class="asc">Ascendia</th><th>Traditional Job Portals</th><th>Company Career Website</th><th>Enterprise HR Software</th></tr></thead><tbody>@foreach([['Job discovery','✓','✓','✓','Limited'],['Verified jobs','✓','Partial','✓','Limited'],['Skill matching','✓','Partial','Limited','✓'],['Learning & interview preparation','✓','Partial','—','Partial'],['Resume builder','✓','✓','—','—'],['Career & industry intelligence','✓','Limited','Company-specific','Internal'],['Candidate skill gap','✓','Limited','—','✓'],['Candidate verification','✓','Limited','Company-specific','✓'],['Digital onboarding','✓','Limited','Company-specific','✓'],['Employee record','✓','—','—','✓'],['Hiring → employee journey','✓','Partial','Company-specific','✓']] as $row)<tr>@foreach($row as $cell)<td class="{{$loop->parent->index===0&&$loop->index===1?'asc yes':($loop->index===1?'asc':'')}}">{{$cell}}</td>@endforeach</tr>@endforeach</tbody></table></div><div class="employer-value"><div class="employer-copy"><span class="why-label">FOR EMPLOYERS</span><h2>Keep your existing hiring channels. Let Ascendia handle the work around them.</h2><p>Continue using your company career website, LinkedIn, Naukri, Indeed, referrals or agencies. Ascendia becomes the operational layer between candidate selection and employee readiness.</p></div><div class="workflow-card">@foreach(['Candidate sourced','Candidate selected','Offer accepted','Verification','Digital onboarding','Employee ready'] as $step)<div class="workflow-step {{$loop->last?'active':''}}"><span>{{str_pad($loop->iteration,2,'0',STR_PAD_LEFT)}}</span>{{$step}}</div>@unless($loop->last)<div class="workflow-line"></div>@endunless @endforeach</div></div><div class="usp"><h2>Your hiring source can be anywhere. Your employee journey can be here.</h2><div class="usp-flow"><span>Any hiring source</span><b>→</b><span>Candidate selected</span><b>→</b><span>Offer</span><b>→</b><span>Verification</span><b>→</b><span>Documents</span><b>→</b><span>Onboarding</span><b>→</b><span>Employee ready</span></div></div></section>
      <section class="journey"><div class="section-header"><div><h2>Your complete career journey</h2><p>Everything works from this one homepage.</p></div></div><div class="journey-grid"><a class="journey-card" href="/about"><b>01</b><h3>About EngineerLaunch</h3><p>Our mission, platform model and promise to job seekers.</p><span>Read about us →</span></a><a class="journey-card" href="/learn"><b style="background:#7c3aed">02</b><h3>Learning & interview preparation</h3><p>Java, Spring Boot, Kafka, Laravel, DSA, system design, ML and data science.</p><span>Start learning →</span></a><a class="journey-card" href="/practice"><b style="background:#0891b2">03</b><h3>Live practice editor</h3><p>Write and run browser-safe HTML, CSS and JavaScript.</p><span>Open editor →</span></a></div></section></main>
<script>
const state={company:'',sort:'posted_at',preset:''};
const $=s=>document.querySelector(s),esc=v=>String(v??'').replace(/[&<>'"]/g,c=>({'&':'&amp;','<':'&lt;','>':'&gt;',"'":'&#39;','"':'&quot;'}[c]));
async function api(path){const key='ascendia:api:v4:'+path,now=Date.now();try{const saved=JSON.parse(sessionStorage.getItem(key)||'null');if(saved&&saved.expires>now)return saved.payload}catch(error){sessionStorage.removeItem(key)}const response=await fetch(path,{headers:{Accept:'application/json'},cache:'no-store'});if(!response.ok)throw new Error('Request failed');const payload=await response.json();try{const total=Number(payload?.pagination?.total??payload?.meta?.total??payload?.total??payload?.data?.total_jobs??0);if(total>0)sessionStorage.setItem(key,JSON.stringify({expires:now+300000,payload}));else sessionStorage.removeItem(key)}catch(error){}return payload}
function initials(name){return String(name||'CO').split(/\s+/).slice(0,2).map(x=>x[0]).join('').toUpperCase()}
function companyLogo(company){const fallback=`<span class="logo-fallback">${esc(initials(company?.name))}</span>`;if(!company?.website)return fallback;const source=`https://www.google.com/s2/favicons?domain_url=${encodeURIComponent(company.website)}&sz=128`;return `<img src="${esc(source)}" alt="${esc(company.name)} logo" loading="lazy" referrerpolicy="no-referrer" onerror="this.style.display='none';this.nextElementSibling.style.display='grid'"><span class="logo-fallback" style="display:none">${esc(initials(company.name))}</span>`}
function jobCard(job){const tags=[...(job.technologies||[]).map(x=>x.name),...(job.categories||[]).map(x=>x.name)].slice(0,5);const description=String(job.description||'Review the complete role requirements on the official company careers page.').replace(/<[^>]*>/g,' ');const isPortal=job.source==='employer'&&job.slug;const applyUrl=isPortal?`/jobs/${encodeURIComponent(job.slug)}`:(job.external_url||job.company?.careers_url||'#');const companyName=job.company?.name||'Company';const reviewCount=Number(job.company?.published_reviews_count||0);const reviewAverage=Number(job.company?.published_reviews_avg_rating||0);const rating=reviewCount?`<span class="company-rating"><span class="star">★</span> ${reviewAverage.toFixed(1)} · ${reviewCount.toLocaleString()} ${reviewCount===1?'review':'reviews'}</span>`:'';return `<article class="job-card"><div class="job-top"><div class="company-info"><div class="job-logo">${esc(initials(companyName))}</div><div><div class="company-small" data-company-name="${esc(companyName)}">${esc(companyName)}${rating}</div><h3 class="job-title">${esc(job.title)}</h3><div class="job-meta"><span>📍 ${esc(job.location||'Not specified')}</span><span>● ${esc(job.job_type||'Full-time')}</span>${job.work_mode?`<span>● ${esc(job.work_mode)}</span>`:''}</div></div></div></div><p class="job-description">${esc(description)}</p><div class="job-bottom"><div class="tags">${tags.map(tag=>`<span class="tag">${esc(tag)}</span>`).join('')}</div><a class="apply" href="${esc(applyUrl)}" ${isPortal?'':'target="_blank" rel="noopener noreferrer"'}>${isPortal?'View & apply →':'View official job →'}</a></div></article>`}
function parameters(){const p=new URLSearchParams({per_page:20,sort_by:state.sort,sort_order:'desc'});const values={region:$('#geo-region').value,country:$('#geo-country').value,state:$('#geo-state').value,city:$('#geo-city').value.trim(),q:$('#keyword').value.trim(),location:$('#location').value.trim(),company_id:state.company,work_mode:$('#work-mode').value,job_type:$('#job-type').value,experience_years:$('#experience').value,posted_within_days:$('#posted').value,role_family:$('#role').value,technology_id:$('#technology').value};if(state.preset==='graduate')values.q='graduate';if(state.preset==='remote')values.work_mode='remote';Object.entries(values).forEach(([key,value])=>{if(value!==''&&value!=null)p.set(key,value)});return p}
let jobsRequest=0;
async function loadJobs(){const request=++jobsRequest;const list=$('#jobs-list');list.innerHTML='<div class="empty">Loading opportunities…</div>';try{const payload=await api('/api/jobs?'+parameters());if(request!==jobsRequest)return;$('#result-count').textContent=`${payload.pagination.total.toLocaleString()} matching roles`;list.innerHTML=payload.data.length?payload.data.map(jobCard).join(''):'<div class="empty"><strong>No exact matches.</strong><br>Try fewer keywords or clear one of the filters.</div>'}catch(error){if(request!==jobsRequest)return;list.innerHTML='<div class="empty">Jobs could not be loaded. Please retry.</div>'}}
async function initialize(){const[statsResult,companiesResult,technologiesResult]=await Promise.allSettled([api('/api/jobs/stats'),api('/api/companies/top-hiring?limit=12'),api('/api/technologies?per_page=100')]);if(statsResult.status==='fulfilled'){const s=statsResult.value.data,totalJobs=Number(s.total_jobs||0);$('#job-count').textContent=totalJobs.toLocaleString();$('#company-count').textContent=Number(s.total_companies||0).toLocaleString();$('#hiring-company-count').textContent=Number(s.hiring_companies||0).toLocaleString();$('#technology-count').textContent=Number(s.total_technologies||0).toLocaleString();$('#available-label').textContent=totalJobs>0?`${totalJobs.toLocaleString()} verified opportunities`:'No verified opportunities available'}if(companiesResult.status==='fulfilled'){$('#company-grid').innerHTML=companiesResult.value.data.slice(0,12).map(company=>`<button class="company-card" data-company="${company.id}"><span class="company-logo">${esc(initials(company.name))}</span><span class="company-name">${esc(company.name)}</span><span class="company-jobs">${Number(company.active_jobs_count||0).toLocaleString()} active jobs</span></button>`).join('')}else{$('#company-grid').innerHTML='<div class="empty">Company data unavailable.</div>'}if(technologiesResult.status==='fulfilled'){$('#technology').insertAdjacentHTML('beforeend',technologiesResult.value.data.map(item=>`<option value="${item.id}">${esc(item.name)}</option>`).join(''))}loadJobs()}
$('#search-form').addEventListener('submit',event=>{event.preventDefault();state.company='';state.preset='';loadJobs();$('#jobs').scrollIntoView()});$('#company-grid').addEventListener('click',event=>{const card=event.target.closest('[data-company]');if(!card)return;document.querySelectorAll('.company-card').forEach(x=>x.classList.remove('active'));card.classList.add('active');state.company=card.dataset.company;loadJobs();$('#jobs').scrollIntoView()});$('#all-companies').addEventListener('click',()=>{state.company='';document.querySelectorAll('.company-card').forEach(x=>x.classList.remove('active'));loadJobs()});document.querySelectorAll('.tab[data-sort],.tab[data-preset]').forEach(button=>button.addEventListener('click',()=>{document.querySelectorAll('.job-toolbar .tab').forEach(x=>x.classList.remove('active'));button.classList.add('active');state.sort=button.dataset.sort||'posted_at';state.preset=button.dataset.preset||'';loadJobs()}));['work-mode','job-type','experience','posted','role','technology'].forEach(id=>$('#'+id).addEventListener('change',loadJobs));$('#clear-filters').addEventListener('click',()=>{['work-mode','job-type','experience','posted','role','technology'].forEach(id=>$('#'+id).value='');$('#keyword').value='';$('#location').value='';['geo-region','geo-country','geo-state','geo-city'].forEach(id=>$('#'+id).value='');updateGeography();state.company='';state.preset='';loadJobs()});initialize();

let geographyRequest=0,cityTimer;
function fillGeo(id,items,label){const el=$('#'+id),selected=el.value;el.innerHTML=`<option value="">${esc(label)}</option>`+items.map(x=>`<option value="${esc(x.value)}">${esc(x.label)}</option>`).join('');if([...el.options].some(x=>x.value===selected))el.value=selected;}
async function updateGeography(){
 const request=++geographyRequest,country=$('#geo-country').value;
 $('#geo-state').disabled=true;$('#geo-city').disabled=!country;
 const params=new URLSearchParams({region:$('#geo-region').value,country,state:$('#geo-state').value,q:$('#geo-city').value.trim()});
 try{const response=await fetch('/api/jobs/locations?'+params,{headers:{Accept:'application/json'}});if(!response.ok)throw Error('Location options unavailable');const {data}=await response.json();if(request!==geographyRequest)return;
 $('#geo-state').disabled=!country;fillGeo('geo-region',data.regions,'All regions');fillGeo('geo-country',data.countries,'All countries');fillGeo('geo-state',data.states,country?'All states / provinces':'Select a country first');
 $('#geo-cities').innerHTML=data.cities.map(x=>`<option value="${esc(x.value)}"></option>`).join('');
 $('#geo-city').placeholder=country?'Type a city, e.g. Bengaluru':'Choose country, then type city';
 $('#geo-status').textContent=country?'Type a city to narrow suggestions. Places may have no current openings.':'Choose a country to browse states and cities.';
 }catch(error){if(request===geographyRequest)$('#geo-status').textContent='Location options could not load. Change a selection to retry.';}
}
$('#geo-region').addEventListener('change',()=>{['geo-country','geo-state','geo-city'].forEach(id=>$('#'+id).value='');updateGeography();loadJobs()});
$('#geo-country').addEventListener('change',()=>{['geo-state','geo-city'].forEach(id=>$('#'+id).value='');updateGeography();loadJobs()});
$('#geo-state').addEventListener('change',()=>{$('#geo-city').value='';updateGeography();loadJobs()});
$('#geo-city').addEventListener('input',()=>{clearTimeout(cityTimer);cityTimer=setTimeout(()=>{updateGeography();loadJobs()},300)});
updateGeography();
</script><script>
function attachSuggestions(inputId,type){const input=document.getElementById(inputId),box=document.getElementById(`${inputId}-suggestions`);let timer,controller;const close=()=>{box.classList.remove('open');box.innerHTML=''};input.addEventListener('input',()=>{clearTimeout(timer);controller?.abort();const q=input.value.trim();if(!q){close();return}timer=setTimeout(async()=>{controller=new AbortController();try{const response=await fetch(`/api/search/suggestions?q=${encodeURIComponent(q)}&type=${type}`,{headers:{Accept:'application/json'},signal:controller.signal});if(!response.ok)throw new Error('Suggestions unavailable');const items=(await response.json()).data||[];box.innerHTML=items.map(item=>`<button type="button" class="suggestion" data-value="${esc(item.value)}"><span>${esc(item.value)}</span><small>${esc(item.type)}</small></button>`).join('');box.classList.toggle('open',items.length>0)}catch(error){if(error.name!=='AbortError')close()}},180)});box.addEventListener('mousedown',event=>{const option=event.target.closest('[data-value]');if(!option)return;event.preventDefault();input.value=option.dataset.value;close();input.focus()});input.addEventListener('keydown',event=>{if(event.key==='Escape')close()});document.addEventListener('click',event=>{if(!event.target.closest('.autocomplete-field'))close()})}
attachSuggestions('keyword','keyword');attachSuggestions('location','location');
</script><script>
let companyLogoDirectory={byId:{},byName:{}};
function paintCompanyLogos(){document.querySelectorAll('.company-card[data-company]').forEach(card=>{const company=companyLogoDirectory.byId[card.dataset.company],target=card.querySelector('.company-logo');if(company&&target)target.innerHTML=companyLogo(company)});document.querySelectorAll('.job-card').forEach(card=>{const companyLabel=card.querySelector('.company-small'),name=String(companyLabel?.dataset.companyName||companyLabel?.textContent||'').trim().toLowerCase(),company=companyLogoDirectory.byName[name],target=card.querySelector('.job-logo');if(company&&target)target.innerHTML=companyLogo(company)})}
api('/api/companies?per_page=100').then(payload=>{payload.data.forEach(company=>{companyLogoDirectory.byId[String(company.id)]=company;companyLogoDirectory.byName[String(company.name).toLowerCase()]=company});paintCompanyLogos();new MutationObserver(paintCompanyLogos).observe(document.querySelector('#jobs-list'),{childList:true})}).catch(()=>{});
</script></body></html>
