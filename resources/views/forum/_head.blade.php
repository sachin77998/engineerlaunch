{{-- Forum header, shared styles and the "Ask your question" dialog. Optional $category preselects the ask form; $cta = [label, route] for the sticky button. --}}
@include('properties._styles')
@push('styles')<style>
.fo-top{background:#fff;border-bottom:1px solid var(--pr-line)}.fo-top .pr-wrap{display:flex;align-items:center;gap:12px;min-height:64px;flex-wrap:wrap}
.fo-top .pr-brand small{font-size:11px;background:#111827;color:#fff;padding:2px 6px;margin-left:6px;letter-spacing:.08em}
.fo-top nav{margin-left:auto;display:flex;gap:8px}.fo-top nav a{border-radius:8px;padding:9px 14px;font-weight:800;text-decoration:none;font-size:14px}
.fo-wrap{width:min(780px,calc(100% - 32px));margin:0 auto}
.fo-crumb{background:#f3f4f6;border-radius:6px;padding:9px 12px;font-size:13px;margin:16px 0}.fo-crumb a{color:#111827;text-decoration:none}
.fo-hero{background:#f9fafb;border-radius:8px;box-shadow:0 2px 8px rgba(17,24,39,.08);padding:26px;text-align:center}
.fo-hero h1{margin:0 0 14px;font-size:30px}.fo-search{display:flex;border:1px solid #e11d48;border-radius:4px;max-width:520px;margin:0 auto}.fo-search input{border:0!important;font-size:17px!important}.fo-search button{border:0;background:none;padding:0 12px;cursor:pointer;font-size:18px}
.fo-green{background:#0f766e;color:#fff;border:0;border-radius:4px;padding:9px 14px;font-weight:800;font-size:16px;cursor:pointer;text-decoration:none;display:inline-block}.fo-green:hover{background:#115e59;color:#fff}
.fo-h2{text-align:center;font-size:26px;margin:28px 0 14px}
.fo-q{background:#f9fafb;border-radius:8px;box-shadow:0 2px 8px rgba(17,24,39,.08);padding:20px;margin-bottom:16px;display:grid;grid-template-columns:52px minmax(0,1fr) 120px;gap:6px 10px}
.fo-q .lbl{font-size:24px;font-weight:800}.fo-q h3{margin:2px 0;font-size:18px;line-height:1.35}.fo-q h3 a{color:#111827;text-decoration:none}.fo-q .meta{text-align:right;color:#6b7280;font-size:12.5px}
.fo-q p{margin:0;color:#1f2937;line-height:1.55}.fo-more{grid-column:3;justify-self:end;align-self:end;background:#f43f5e;color:#fff;border-radius:6px;padding:4px 10px;text-decoration:none;font-size:15px}
.fo-cats{display:grid;grid-template-columns:repeat(auto-fill,minmax(220px,1fr));gap:12px}.fo-cats a{background:#fff;border:1px solid var(--pr-line);border-radius:10px;padding:14px;text-decoration:none;color:inherit}.fo-cats small{display:block;color:var(--pr-mute)}
.fo-sticky{position:fixed;left:50%;transform:translateX(-50%);bottom:12px;background:#f43f5e;color:#fff;font-weight:800;padding:9px 14px;border-radius:4px;text-decoration:none;font-size:18px;z-index:40;box-shadow:0 6px 16px rgba(244,63,94,.35)}
dialog.fo-dlg{border:0;border-radius:10px;padding:0;width:min(620px,calc(100% - 24px))}dialog.fo-dlg::backdrop{background:rgba(0,0,0,.45)}
.fo-dlg form{padding:20px;display:grid;gap:12px}.fo-dlg header{display:flex;justify-content:space-between;align-items:center;padding:14px 20px;border-bottom:1px solid var(--pr-line)}.fo-dlg header button{border:0;background:none;font-size:24px;cursor:pointer}
@media(max-width:600px){.fo-q{grid-template-columns:40px minmax(0,1fr)}.fo-q .meta{grid-column:2;text-align:left}.fo-more{grid-column:2}}
</style>@endpush
<div class="fo-top"><div class="pr-wrap">
    <a class="pr-brand" href="{{ route('forum.index') }}"><i>?</i>{{ config('properties.brand') }}<small>FORUM</small></a>
    <nav><a href="{{ route('properties.post') }}" style="background:#f43f5e;color:#fff">Post property ad</a><a href="{{ route('properties.search', 'rent') }}" style="background:#111827;color:#fff">Rent / Buy</a></nav>
</div></div>
@auth
<dialog class="fo-dlg" id="fo-ask" @if($errors->has('title') || $errors->has('category')) data-reopen @endif>
    <header><b style="font-size:18px">Ask your question</b><button type="button" data-close aria-label="Close">×</button></header>
    <form method="post" action="{{ route('forum.ask') }}">
        @csrf
        @if($errors->has('title') || $errors->has('category'))<div class="pr-flash err" style="margin:0">{{ $errors->first('title') ?: $errors->first('category') }}</div>@endif
        <select name="category" required aria-label="Category">@foreach(config('forum.categories') as $k => [$label])<option value="{{ $k }}" @selected(old('category', $category ?? null) === $k)>{{ $label }}</option>@endforeach</select>
        <input type="text" name="title" required minlength="{{ config('forum.title_min') }}" maxlength="{{ config('forum.title_max') }}" value="{{ old('title') }}" placeholder="Your question, e.g. How do I transfer khata after buying a flat?" aria-label="Question">
        <textarea name="body" rows="4" maxlength="{{ config('forum.body_max') }}" placeholder="Details (optional) — don't share phone numbers or addresses" aria-label="Details">{{ old('body') }}</textarea>
        <input type="text" name="city" maxlength="80" value="{{ old('city') }}" placeholder="City (optional)" aria-label="City">
        <button class="fo-green">Post question</button>
    </form>
</dialog>
@endauth
@isset($cta)<a class="fo-sticky" href="{{ route($cta[1]) }}">{{ $cta[0] }}</a>@endisset
@push('scripts')<script>
(function () {
    var d = document.getElementById('fo-ask');
    document.querySelectorAll('[data-ask]').forEach(function (b) { b.addEventListener('click', function (e) { if (!d) return; e.preventDefault(); d.showModal(); }); });
    if (d) { d.querySelector('[data-close]').addEventListener('click', function () { d.close(); }); if (d.hasAttribute('data-reopen')) d.showModal(); }
})();
</script>@endpush
