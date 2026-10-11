{{-- Switches between pages that share one header menu entry. $tabs: [[label, url, active], ...] --}}
<nav class="page-tabs" aria-label="Related pages">
    @foreach($tabs as [$label, $url, $active])
        <a href="{{ $url }}" @if($active) aria-current="page" @endif>{{ $label }}</a>
    @endforeach
</nav>
@once
<style>
.page-tabs{display:flex;flex-wrap:wrap;gap:6px;width:min(1250px,calc(100% - 32px));margin:16px auto;padding:5px;background:#fff;border:1px solid #dfe6f1;border-radius:12px;box-shadow:0 4px 14px rgba(11,37,69,.06);box-sizing:border-box}
.page-tabs a{flex:1 1 200px;padding:10px 14px;border-radius:9px;color:#0b2545;font:700 14px/1.3 Inter,system-ui,sans-serif;text-align:center;text-decoration:none}
.page-tabs a:hover{background:#fff6dc}
.page-tabs a[aria-current=page]{background:#0b2545;color:#fff}
</style>
@endonce
