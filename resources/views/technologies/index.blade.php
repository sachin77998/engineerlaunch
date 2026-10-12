@extends('layouts.site')
@section('title', 'Jobs by Language & Technology — Ascendia')
@push('styles')<style>
.tch{width:min(1250px,calc(100% - 32px));margin:24px auto 56px;font-family:Inter,system-ui,sans-serif}
.tch h1{margin:0 0 4px;color:#0b2545}.tch-sub{margin:0 0 16px;color:#5b6b82}
.tch-bar{position:sticky;top:0;z-index:5;display:flex;flex-wrap:wrap;gap:10px;align-items:center;padding:10px 0;background:var(--bg,#f3f8ff)}
.tch-bar input[type=search]{flex:1 1 280px;padding:11px 14px;border:1px solid #d7e1ef;border-radius:11px;font:inherit;background:#fff}
.tch-bar label{display:flex;gap:6px;align-items:center;font-size:14px;color:#334155}
.tch-nav{display:flex;flex-wrap:wrap;gap:6px;margin:6px 0 18px}.tch-nav a{padding:6px 11px;border-radius:999px;background:#fff;border:1px solid #dfe6f1;color:#0b2545;text-decoration:none;font-size:13px;font-weight:600}
.tch-sec{margin-bottom:26px;scroll-margin-top:70px}.tch-sec h2{font-size:19px;color:#0b2545;margin:0 0 10px}.tch-sec h2 small{color:#5b6b82;font-weight:500;font-size:13px}
.tch-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(190px,1fr));gap:10px}
.tch-item{display:flex;justify-content:space-between;align-items:center;gap:8px;padding:11px 13px;border:1px solid #e2e8f2;border-radius:11px;background:#fff;color:#0f1c2e;text-decoration:none;font-weight:600;font-size:14px}
.tch-item:hover{border-color:#f4b400;box-shadow:0 6px 16px rgba(11,37,69,.08)}
.tch-item b{flex:none;padding:2px 8px;border-radius:999px;background:#0b2545;color:#fff;font-size:11.5px}.tch-item b.zero{background:#eef2f7;color:#64748b}
.tch-empty{padding:20px;color:#5b6b82}
</style>@endpush
@section('content')
<main class="tch">
    <h1>Jobs by Language &amp; Technology</h1>
    <p class="tch-sub">{{ number_format($total) }} programming languages, frameworks, databases and tools · {{ number_format($withJobs) }} with live openings. Updated automatically from open data and new job postings.</p>
    <div class="tch-bar">
        <input type="search" id="tch-q" placeholder="Search a language or tool, e.g. Perl, Swift, Ruby on Rails, Kubernetes" aria-label="Search technologies">
        <label><input type="checkbox" id="tch-open"> Only with openings</label>
    </div>
    <nav class="tch-nav" aria-label="Sections">@foreach($sections as $name => $list)<a href="#{{ \Illuminate\Support\Str::slug($name) }}">{{ $name }} · {{ $list->count() }}</a>@endforeach</nav>
    @foreach($sections as $name => $list)
        <section class="tch-sec" id="{{ \Illuminate\Support\Str::slug($name) }}">
            <h2>{{ $name }} <small>{{ $list->count() }}</small></h2>
            <div class="tch-grid">
                @foreach($list as $technology)
                    <a class="tch-item" href="{{ route('technologies.show', $technology->slug) }}" data-name="{{ mb_strtolower($technology->name) }}" data-jobs="{{ $technology->jobs_count }}">{{ $technology->name }}<b class="{{ $technology->jobs_count ? '' : 'zero' }}">{{ number_format($technology->jobs_count) }}</b></a>
                @endforeach
            </div>
        </section>
    @endforeach
    <p class="tch-empty" id="tch-none" hidden>No technology matches your search.</p>
</main>
@endsection
@push('scripts')<script>
(() => {
    const q = document.getElementById('tch-q'), open = document.getElementById('tch-open');
    const filter = () => {
        const term = q.value.trim().toLowerCase(); let shown = 0;
        document.querySelectorAll('.tch-sec').forEach(sec => {
            let visible = 0;
            sec.querySelectorAll('.tch-item').forEach(item => {
                const ok = (!term || item.dataset.name.includes(term)) && (!open.checked || Number(item.dataset.jobs) > 0);
                item.hidden = !ok; if (ok) visible++;
            });
            sec.hidden = visible === 0; shown += visible;
        });
        document.getElementById('tch-none').hidden = shown > 0;
    };
    q.addEventListener('input', filter); open.addEventListener('change', filter);
})();
</script>@endpush
