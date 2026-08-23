@extends('layouts.app')

@section('title', 'About Us — EngineerLaunch')

{{-- Make sure Bootstrap Icons are available. If your main layout already
     loads them, delete this block — it's guarded with @once so it's safe
     either way. --}}
@push('styles')
    @once
        <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
        <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    @endonce
@endpush

@section('content')

{{-- ============================================
     1. INTRO — Who We Are
============================================ --}}
<section class="py-5 py-lg-6" style="background: linear-gradient(180deg,#eef4ff 0%,#ffffff 100%);">
    <div class="container py-5">
        <div class="col-lg-8 mx-auto text-center">
            <span class="badge rounded-pill bg-primary-subtle text-primary fw-semibold px-3 py-2 mb-4">
                Who we are
            </span>
            <h1 class="fw-bold display-5 mb-4">
                More than a job portal.<br>
                We're a <span class="text-primary">career ecosystem.</span>
            </h1>
            <p class="lead text-muted mx-auto" style="max-width: 640px;">
                Job discovery, skill-building, resume writing, and interview practice usually live
                on separate websites. EngineerLaunch brings all four into one platform — so your
                career depends on your skills, not on how many tabs you have open.
            </p>
        </div>
    </div>
</section>

{{-- ============================================
     2. VISION
============================================ --}}
<section class="py-5 py-lg-6 bg-white">
    <div class="container py-4">
        <div class="row align-items-center g-5">
            <div class="col-lg-6">
                <span class="text-uppercase text-primary fw-semibold small">Our vision</span>
                <h2 class="fw-bold mt-2 mb-3">One platform. Every opportunity.</h2>
                <p class="text-muted mb-0">
                    A student's future shouldn't hinge on whether they happened to know the right
                    website, company, or contact. If you have the skill and the drive, you should be
                    able to find the opportunity that matches it — every time.
                </p>
            </div>

            <div class="col-lg-6">
                <ul class="list-unstyled d-flex flex-column gap-3 mb-0">
                    <li class="d-flex gap-3">
                        <i class="bi bi-check-circle-fill text-primary fs-5 mt-1"></i>
                        <span class="text-muted"><strong class="text-dark">Everything in one place</strong> — learning, practice, resume, and jobs under a single profile.</span>
                    </li>
                    <li class="d-flex gap-3">
                        <i class="bi bi-check-circle-fill text-primary fs-5 mt-1"></i>
                        <span class="text-muted"><strong class="text-dark">Built for real skill signal</strong> — what you can do matters more than what you happened to find.</span>
                    </li>
                    <li class="d-flex gap-3">
                        <i class="bi bi-check-circle-fill text-primary fs-5 mt-1"></i>
                        <span class="text-muted"><strong class="text-dark">Designed to grow with you</strong> — from your first application to your next career move.</span>
                    </li>
                </ul>
            </div>
        </div>
    </div>
</section>

{{-- ============================================
     3. THE PROBLEM
============================================ --}}
<section class="py-5 py-lg-6 bg-light">
    <div class="container py-4">
        <div class="col-lg-7 mx-auto text-center mb-5">
            <span class="text-uppercase text-primary fw-semibold small">The problem we're solving</span>
            <h2 class="fw-bold mt-2 mb-3">Good opportunities get lost in too many places.</h2>
            <p class="text-muted mb-0">
                Job boards, company career pages, social media, course sites, and practice platforms
                all live separately. A capable student can still lose out on a great role — simply
                because they never saw it.
            </p>
        </div>

        <div class="row row-cols-2 row-cols-md-4 g-3 col-lg-10 mx-auto">
            @foreach (['Job portals', 'Company career pages', 'Social media', 'Technology blogs', 'Learning platforms', 'Resume tools', 'Coding practice sites', 'News platforms'] as $source)
                <div class="col">
                    <div class="border rounded-3 bg-white text-center py-3 px-2 small fw-semibold text-muted h-100 d-flex align-items-center justify-content-center">
                        {{ $source }}
                    </div>
                </div>
            @endforeach
        </div>

        <p class="text-center fw-semibold fs-5 mt-5 mb-0">
            One login. One profile. One career ecosystem.
        </p>
    </div>
</section>

{{-- ============================================
     4. HOW WE HELP  (card system starts here)
============================================ --}}
<section class="py-5 py-lg-6 bg-white">
    <div class="container py-4">
        <div class="col-lg-7 mx-auto text-center mb-5">
            <span class="text-uppercase text-primary fw-semibold small">How we help</span>
            <h2 class="fw-bold mt-2 mb-0">Everything a career journey needs, in one place.</h2>
        </div>

        <div class="row row-cols-1 row-cols-md-2 g-4">
            @foreach ([
                ['icon' => 'bi-search', 'title' => 'Discover the right openings', 'text' => 'Filter roles by skill, technology, experience, location, company, or industry — for freshers and experienced professionals alike.'],
                ['icon' => 'bi-building', 'title' => 'Know who\'s hiring, right now', 'text' => 'See active hiring activity across companies instead of checking career pages one by one, every day.'],
                ['icon' => 'bi-file-earmark-text', 'title' => 'Build a resume that gets through', 'text' => 'Turn your skills, projects, and experience into a clear, ATS-friendly resume recruiters actually see.'],
                ['icon' => 'bi-mortarboard', 'title' => 'Learn what the market needs now', 'text' => 'Explore in-demand languages, frameworks, and skills as the technology landscape shifts.'],
            ] as $card)
                <div class="col">
                    <div class="card h-100 border rounded-4 p-2">
                        <div class="card-body">
                            <div class="d-inline-flex align-items-center justify-content-center bg-primary-subtle text-primary rounded-3 mb-3" style="width:48px;height:48px;">
                                <i class="bi {{ $card['icon'] }} fs-4"></i>
                            </div>
                            <h3 class="h5 fw-bold">{{ $card['title'] }}</h3>
                            <p class="text-muted mb-0">{{ $card['text'] }}</p>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
    </div>
</section>

{{-- ============================================
     5. THE JOURNEY — real sequence, numbered
============================================ --}}
<section class="py-5 py-lg-6 bg-light">
    <div class="container py-4">
        <div class="col-lg-7 mx-auto text-center mb-5">
            <span class="text-uppercase text-primary fw-semibold small">The journey</span>
            <h2 class="fw-bold mt-2 mb-3">One step at a time, from student to hire.</h2>
            <p class="text-muted mb-0">We're not aiming for one job. We're building a long-term career path.</p>
        </div>

        <div class="row row-cols-2 row-cols-md-4 g-4">
            @foreach ([
                ['n' => '01', 'title' => 'Learn', 'text' => 'Pick up the technologies your career actually needs.'],
                ['n' => '02', 'title' => 'Practice', 'text' => 'Sharpen coding and problem-solving with real practice.'],
                ['n' => '03', 'title' => 'Build', 'text' => 'Turn practice into projects and a stronger profile.'],
                ['n' => '04', 'title' => 'Prepare', 'text' => 'Put it all into an ATS-friendly resume.'],
                ['n' => '05', 'title' => 'Discover', 'text' => 'Find companies and roles worth applying to.'],
                ['n' => '06', 'title' => 'Apply', 'text' => 'Go after roles that fit your skills and goals.'],
                ['n' => '07', 'title' => 'Grow', 'text' => 'Keep learning as the market moves forward.'],
            ] as $step)
                <div class="col">
                    <div class="card h-100 border-0 shadow-sm rounded-4">
                        <div class="card-body">
                            <span class="d-inline-flex align-items-center justify-content-center bg-primary-subtle text-primary fw-bold rounded-3 mb-3" style="width:40px;height:40px;">
                                {{ $step['n'] }}
                            </span>
                            <h3 class="h6 fw-bold">{{ $step['title'] }}</h3>
                            <p class="text-muted small mb-0">{{ $step['text'] }}</p>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
    </div>
</section>

{{-- ============================================
     6. ALWAYS CURRENT
============================================ --}}
<section class="py-5 py-lg-6 bg-white">
    <div class="container py-4">
        <div class="col-lg-8 mx-auto text-center">
            <div class="d-inline-flex align-items-center justify-content-center bg-primary-subtle text-primary rounded-circle mb-4" style="width:56px;height:56px;">
                <i class="bi bi-arrow-repeat fs-3"></i>
            </div>
            <h2 class="fw-bold mb-3">Built to stay current, not catch up later.</h2>
            <p class="text-muted mb-0">
                New roles open, companies start hiring, and frameworks change every day. EngineerLaunch
                refreshes continuously — so what you see is what's happening now, not what was posted last month.
            </p>
        </div>
    </div>
</section>

{{-- ============================================
     7. THE MARKETPLACE (same card system)
============================================ --}}
<section class="py-5 py-lg-6 bg-light">
    <div class="container py-4">
        <div class="col-lg-7 mx-auto text-center mb-5">
            <span class="text-uppercase text-primary fw-semibold small">The marketplace</span>
            <h2 class="fw-bold mt-2 mb-0">Everything you need, under one roof.</h2>
        </div>

        <div class="row row-cols-2 row-cols-md-4 g-4">
            @foreach ([
                ['icon' => 'bi-search', 'title' => 'Jobs', 'text' => 'Openings matched to your skills.'],
                ['icon' => 'bi-building', 'title' => 'Companies', 'text' => 'Hiring activity and career paths.'],
                ['icon' => 'bi-file-earmark-text', 'title' => 'Resume Builder', 'text' => 'ATS-friendly resumes, done right.'],
                ['icon' => 'bi-mortarboard', 'title' => 'Learning', 'text' => 'Skills the market wants today.'],
                ['icon' => 'bi-code-slash', 'title' => 'Practice', 'text' => 'Coding, DSA, problem-solving.'],
                ['icon' => 'bi-rocket-takeoff', 'title' => 'Technology', 'text' => 'Languages, frameworks, tools.'],
                ['icon' => 'bi-newspaper', 'title' => 'Market News', 'text' => 'What\'s moving in tech hiring.'],
                ['icon' => 'bi-graph-up-arrow', 'title' => 'Career Growth', 'text' => 'What to learn, and where next.'],
            ] as $card)
                <div class="col">
                    <div class="card h-100 border-0 shadow-sm rounded-4 text-center">
                        <div class="card-body">
                            <div class="d-inline-flex align-items-center justify-content-center bg-primary-subtle text-primary rounded-3 mb-3" style="width:44px;height:44px;">
                                <i class="bi {{ $card['icon'] }} fs-5"></i>
                            </div>
                            <h3 class="h6 fw-bold mb-1">{{ $card['title'] }}</h3>
                            <p class="text-muted small mb-0">{{ $card['text'] }}</p>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
    </div>
</section>

{{-- ============================================
     8. WHY CHOOSE US (same card system)
============================================ --}}
<section class="py-5 py-lg-6 bg-white">
    <div class="container py-4">
        <div class="col-lg-7 mx-auto text-center mb-5">
            <span class="text-uppercase text-primary fw-semibold small">Why choose us</span>
            <h2 class="fw-bold mt-2 mb-0">One platform, not ten open tabs.</h2>
        </div>

        <div class="row row-cols-1 row-cols-md-3 g-4">
            @foreach ([
                ['icon' => 'bi-compass', 'title' => 'Discover Opportunities', 'text' => 'Find jobs and companies hiring in your field.'],
                ['icon' => 'bi-lightbulb', 'title' => 'Learn Modern Skills', 'text' => 'Stay current with the technologies that matter.'],
                ['icon' => 'bi-code-square', 'title' => 'Practice Regularly', 'text' => 'Build real problem-solving ability, not just theory.'],
                ['icon' => 'bi-file-earmark-check', 'title' => 'Build Your Resume', 'text' => 'A professional, ATS-friendly resume in minutes.'],
                ['icon' => 'bi-bell', 'title' => 'Stay Updated', 'text' => 'Follow what\'s changing in tech and hiring.'],
                ['icon' => 'bi-send-check', 'title' => 'Apply With Confidence', 'text' => 'Go after roles that actually fit you.'],
            ] as $card)
                <div class="col">
                    <div class="card h-100 border rounded-4">
                        <div class="card-body">
                            <div class="d-inline-flex align-items-center justify-content-center bg-primary-subtle text-primary rounded-3 mb-3" style="width:44px;height:44px;">
                                <i class="bi {{ $card['icon'] }} fs-5"></i>
                            </div>
                            <h3 class="h6 fw-bold">{{ $card['title'] }}</h3>
                            <p class="text-muted small mb-0">{{ $card['text'] }}</p>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
    </div>
</section>

{{-- ============================================
     9. CORE BELIEF + CTA
============================================ --}}
<section class="py-5 py-lg-6 bg-dark text-white">
    <div class="container py-5">
        <div class="col-lg-7 mx-auto text-center">
            <span class="badge rounded-pill bg-primary-subtle text-primary fw-semibold px-3 py-2 mb-4">
                Our core belief
            </span>
            <h2 class="fw-bold mb-3">Talent is everywhere. Opportunity should be too.</h2>
            <p class="text-white-50 mb-4">
                Every year, thousands of capable students are held back by one thing — not talent,
                but access to the right information at the right time. EngineerLaunch exists to close
                that gap.
            </p>
            <a href="{{ route('register') }}" class="btn btn-primary btn-lg px-4 rounded-3 fw-semibold">
                Start Your Career Journey
            </a>
        </div>
    </div>
</section>

@endsection
