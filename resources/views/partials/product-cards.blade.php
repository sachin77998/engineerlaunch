@php
    $link = function ($target) {
        [$name, $param] = is_array($target) ? $target : [$target, null];
        return \Illuminate\Support\Facades\Route::has($name) ? route($name, $param) : null;
    };
    $products = collect(config('products'))->map(function ($p) use ($link) {
        $p['url'] = $link($p['details']);
        $p['features'] = collect($p['features'])->map(fn ($f) => [$f[0], $link($f[1])])->filter(fn ($f) => $f[1])->values()->all();
        return $p;
    })->filter(fn ($p) => $p['url'])->values();
    $pages = $products->chunk(6);
@endphp
<section class="pc-section" aria-labelledby="pc-title">
    <h2 id="pc-title">All Available Products</h2>
    <div class="pc-carousel">
        @if($pages->count() > 1)<button type="button" class="pc-arrow prev" aria-label="Previous products">‹</button>@endif
        <div class="pc-track">
            @foreach($pages as $i => $page)
            <div class="pc-page" @if($i > 0) hidden @endif>
                @foreach($page as $p)
                <article class="pc-card">
                    <div class="pc-body">
                        <h3>{{ $p['title'] }}</h3>
                        <div class="pc-logo"><span class="pc-icon" aria-hidden="true">{{ $p['icon'] }}</span><span class="pc-badge" style="color:{{ $p['color'] }}">{{ $p['badge'] }}</span></div>
                        <p>{{ $p['summary'] }}</p>
                        <a class="pc-details" href="{{ $p['url'] }}">Details</a>
                    </div>
                    <button type="button" class="pc-quick" data-pc-open="pc-{{ $p['key'] }}">Quick View</button>
                </article>
                @endforeach
            </div>
            @endforeach
        </div>
        @if($pages->count() > 1)<button type="button" class="pc-arrow next" aria-label="Next products">›</button>@endif
    </div>
    @if($pages->count() > 1)<div class="pc-dots">@foreach($pages as $i => $page)<button type="button" class="{{ $i === 0 ? 'on' : '' }}" aria-label="Products page {{ $i + 1 }}"></button>@endforeach</div>@endif

    @foreach($products as $p)
    <div class="pc-modal" id="pc-{{ $p['key'] }}" hidden role="dialog" aria-modal="true" aria-label="{{ $p['title'] }}">
        <div class="pc-modal-box">
            <button type="button" class="pc-close" aria-label="Close" data-pc-close>×</button>
            <div class="pc-logo" style="justify-content:flex-start;border:0;padding:0"><span class="pc-icon">{{ $p['icon'] }}</span><h3 style="margin:0">{{ $p['title'] }}</h3></div>
            <p>{{ $p['summary'] }}</p>
            <div class="pc-features">@foreach($p['features'] as [$label, $url])<a href="{{ $url }}">{{ $label }} <span>→</span></a>@endforeach</div>
            <a class="pc-open" href="{{ $p['url'] }}" style="background:{{ $p['color'] }}">Open {{ $p['title'] }}</a>
        </div>
    </div>
    @endforeach
</section>
<style>
.pc-section{max-width:1280px;margin:0 auto;padding:44px 16px 30px;font-family:Inter,system-ui,sans-serif}
.pc-section h2{text-align:center;font-size:clamp(28px,4vw,44px);font-weight:800;color:#0b3b8c;margin:0 0 34px}
.pc-carousel{position:relative;padding:0 58px}
.pc-page{display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:36px 56px}
.pc-card{display:flex;flex-direction:column;background:#fff;border-radius:16px;box-shadow:0 6px 24px rgba(16,33,62,.12);overflow:hidden;min-height:420px}
.pc-body{flex:1;display:flex;flex-direction:column;padding:22px 20px 14px}
.pc-body h3{margin:0;color:#0b3b8c;font-size:22px;font-weight:800}
.pc-logo{display:flex;justify-content:flex-end;align-items:center;gap:10px;padding:20px 0 14px;border-bottom:1px solid #e3e8f0}
.pc-icon{font-size:30px;line-height:1}.pc-badge{font:900 italic 22px/1 Inter,system-ui,sans-serif;letter-spacing:.02em}
.pc-body p{margin:16px 0 0;color:#475569;font-size:15px;line-height:1.65;flex:1}
.pc-details{align-self:flex-end;margin-top:12px;color:#0b3b8c;text-decoration:underline;font-size:15px}
.pc-quick{border:0;background:#0b3b8c;color:#fff;font:600 20px Inter,system-ui,sans-serif;padding:18px;cursor:pointer}
.pc-quick:hover{background:#0a3274}
.pc-arrow{position:absolute;top:50%;transform:translateY(-50%);width:54px;height:54px;border-radius:50%;border:2px solid #0b3b8c;background:#fff;color:#0b3b8c;font-size:32px;line-height:1;cursor:pointer;z-index:2}
.pc-arrow.prev{left:0}.pc-arrow.next{right:0}.pc-arrow:hover{background:#eef3ff}
.pc-dots{display:flex;justify-content:center;gap:16px;margin-top:30px}
.pc-dots button{width:16px;height:16px;border-radius:50%;border:2px solid #0b3b8c;background:#fff;padding:0;cursor:pointer}.pc-dots button.on{background:#0b3b8c}
.pc-modal{position:fixed;inset:0;z-index:2000;background:rgba(15,23,42,.55);display:grid;place-items:center;padding:16px}
.pc-modal[hidden]{display:none}
.pc-modal-box{position:relative;width:min(620px,100%);max-height:90vh;overflow:auto;background:#fff;border-radius:16px;padding:28px}
.pc-modal-box p{color:#475569;line-height:1.6}
.pc-close{position:absolute;top:14px;right:14px;width:36px;height:36px;border:0;border-radius:50%;background:#111827;color:#fff;font-size:20px;cursor:pointer}
.pc-features{display:grid;grid-template-columns:repeat(auto-fill,minmax(220px,1fr));gap:8px;margin:14px 0 20px}
.pc-features a{display:flex;justify-content:space-between;padding:11px 14px;border:1px solid #e3e8f0;border-radius:10px;color:#17233c;text-decoration:none;font-weight:600}
.pc-features a:hover{border-color:#0b3b8c;color:#0b3b8c}
.pc-open{display:block;text-align:center;padding:14px;border-radius:10px;color:#fff;font-weight:700;text-decoration:none}
@media(max-width:980px){.pc-page{grid-template-columns:repeat(2,minmax(0,1fr));gap:24px}}
@media(max-width:640px){.pc-page{grid-template-columns:1fr}.pc-carousel{padding:0}.pc-arrow{display:none}.pc-card{min-height:0}}
</style>
<script>
(function () {
    var pages = document.querySelectorAll('.pc-page'), dots = document.querySelectorAll('.pc-dots button'), i = 0;
    function show(n) { if (!pages.length) return; i = (n + pages.length) % pages.length; pages.forEach(function (p, k) { p.hidden = k !== i; }); dots.forEach(function (d, k) { d.classList.toggle('on', k === i); }); }
    var prev = document.querySelector('.pc-arrow.prev'), next = document.querySelector('.pc-arrow.next');
    if (prev) prev.addEventListener('click', function () { show(i - 1); });
    if (next) next.addEventListener('click', function () { show(i + 1); });
    dots.forEach(function (d, k) { d.addEventListener('click', function () { show(k); }); });
    var close = function (m) { m.hidden = true; document.body.style.overflow = ''; };
    document.querySelectorAll('[data-pc-open]').forEach(function (b) { b.addEventListener('click', function () { var m = document.getElementById(b.dataset.pcOpen); m.hidden = false; document.body.style.overflow = 'hidden'; m.querySelector('.pc-close').focus(); }); });
    document.querySelectorAll('.pc-modal').forEach(function (m) { m.addEventListener('click', function (e) { if (e.target === m || e.target.hasAttribute('data-pc-close')) close(m); }); });
    document.addEventListener('keydown', function (e) { if (e.key === 'Escape') document.querySelectorAll('.pc-modal:not([hidden])').forEach(close); });
})();
</script>
