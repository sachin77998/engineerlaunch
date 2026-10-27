@php
    // Every flash message and validation error, shown as one stack of dismissible alerts. A message the page already
    // prints inline (login status, an @error under a field) is skipped: the page content is rendered before its layout.
    $shownInline = $__env->yieldContent('content');
    $kinds = [
        'success' => ['success', 'status', 'message', 'info', 'otp_resent', 'profile_saved', 'review_success'],
        'warning' => ['warning', 'mail_warning'],
        'error' => ['error', 'danger'],
    ];
    $flashes = collect();
    foreach ($kinds as $kind => $keys) {
        foreach ($keys as $key) {
            $v = session($key);
            if (is_string($v) && trim($v) !== '') $flashes->push(['kind' => $kind, 'text' => $v]);
        }
    }
    foreach ((isset($errors) ? $errors->all() : []) as $msg) $flashes->push(['kind' => 'error', 'text' => $msg]);
    $flashes = $flashes->unique('text')->reject(fn ($f) => $shownInline !== '' && str_contains($shownInline, e($f['text'])))->values();
@endphp
@if ($flashes->isNotEmpty())
<div class="flash-stack" aria-live="polite">
    @foreach ($flashes as $f)
    <div class="flash-msg flash-{{ $f['kind'] }}" role="{{ $f['kind'] === 'error' ? 'alert' : 'status' }}" @if($f['kind'] === 'success') data-autohide @endif>
        <span class="flash-icon" aria-hidden="true">{{ $f['kind'] === 'success' ? '✓' : '!' }}</span>
        <span class="flash-text">{{ $f['text'] }}</span>
        <button type="button" class="flash-close" aria-label="Dismiss">×</button>
    </div>
    @endforeach
</div>
<style>
.flash-stack{position:fixed;top:88px;right:16px;z-index:2000;display:grid;gap:10px;width:min(420px,calc(100% - 32px))}
.flash-msg{display:flex;align-items:flex-start;gap:10px;padding:13px 14px;border:1px solid;border-radius:12px;background:#fff;box-shadow:0 12px 30px rgba(16,33,62,.16);font:500 15px/1.45 Inter,system-ui,sans-serif;animation:flash-in .25s ease-out}
.flash-success{border-color:#a6e3c3;background:#f0fbf5;color:#05603a}
.flash-warning{border-color:#fcd68a;background:#fffaeb;color:#93370d}
.flash-error{border-color:#fdb7b0;background:#fef3f2;color:#b42318}
.flash-icon{flex:0 0 22px;height:22px;border-radius:50%;display:grid;place-items:center;font-weight:800;font-size:13px;color:#fff;background:currentColor}
.flash-icon{color:inherit}.flash-success .flash-icon{background:#12b76a;color:#fff}.flash-warning .flash-icon{background:#f79009;color:#fff}.flash-error .flash-icon{background:#f04438;color:#fff}
.flash-text{flex:1;word-break:break-word}
.flash-close{flex:0 0 auto;border:0;background:none;color:inherit;font-size:22px;line-height:1;cursor:pointer;opacity:.6;padding:0 2px}
.flash-close:hover{opacity:1}
.flash-msg.is-hiding{opacity:0;transform:translateY(-6px);transition:opacity .2s,transform .2s}
@keyframes flash-in{from{opacity:0;transform:translateY(-8px)}to{opacity:1;transform:none}}
@media (max-width:640px){.flash-stack{top:auto;bottom:16px;right:16px}}
</style>
<script>
(function () {
    var hide = function (el) { el.classList.add('is-hiding'); setTimeout(function () { el.remove(); }, 220); };
    document.querySelectorAll('.flash-msg').forEach(function (el) {
        el.querySelector('.flash-close').addEventListener('click', function () { hide(el); });
        if (el.hasAttribute('data-autohide')) setTimeout(function () { hide(el); }, 7000);
    });
})();
</script>
@endif
