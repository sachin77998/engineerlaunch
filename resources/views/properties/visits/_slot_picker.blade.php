{{-- Day chips, then that day's time chips (radio "visit_at"). Expects $days (VisitSlots::available) and $pickerId. Works without JS: every day's times are listed. --}}
@once
@push('styles')<style>
.vs-days{display:flex;gap:8px;overflow-x:auto;padding-bottom:4px}
.vs-days button{flex:none;border:1px solid var(--pr-line);background:#fff;border-radius:10px;padding:8px 12px;cursor:pointer;font:600 13.5px Inter,system-ui,sans-serif;text-align:center;line-height:1.3}
.vs-days button.on{border-color:#2563eb;background:#eff6ff;color:#1e3a8a}.vs-days small{display:block;font-weight:500;color:#6b7280}
.vs-times{display:flex;flex-wrap:wrap;gap:8px;margin-top:10px}
.vs-times label{cursor:pointer}.vs-times input{position:absolute;opacity:0;pointer-events:none}
.vs-times span{display:inline-block;border:1px solid var(--pr-line);border-radius:999px;padding:7px 12px;font-size:14px;background:#fff}
.vs-times input:checked+span{background:#2563eb;border-color:#2563eb;color:#fff}.vs-times input:focus-visible+span{outline:2px solid #93c5fd}
.vs-day-title{font-weight:700;font-size:13px;color:#374151;margin-top:10px}
</style>@endpush
@push('scripts')<script>
document.querySelectorAll('[data-slot-picker]').forEach(function (p) {
    var btns = p.querySelectorAll('[data-day]'), panes = p.querySelectorAll('[data-day-pane]');
    function show(d) { btns.forEach(function (b) { b.classList.toggle('on', b.dataset.day === d); }); panes.forEach(function (x) { x.hidden = x.dataset.dayPane !== d; }); }
    btns.forEach(function (b) { b.addEventListener('click', function () { show(b.dataset.day); }); });
    p.querySelectorAll('.vs-day-title').forEach(function (t) { t.hidden = true; });
    if (btns.length) show(btns[0].dataset.day);
});
</script>@endpush
@endonce
@if(empty($days))
    <p class="pr-mute" style="margin:0">No visit slots are open in the next {{ config('site_visits.days_ahead') }} days.</p>
@else
    <div data-slot-picker id="{{ $pickerId }}">
        <div class="vs-days" role="tablist" aria-label="Day">
            @foreach($days as $d)<button type="button" data-day="{{ $d['date'] }}">{{ $d['label'] }}<small>{{ count($d['slots']) }} {{ count($d['slots']) === 1 ? 'slot' : 'slots' }}</small></button>@endforeach
        </div>
        @foreach($days as $d)
            <div data-day-pane="{{ $d['date'] }}">
                <div class="vs-day-title">{{ $d['label'] }}</div>
                <div class="vs-times">@foreach($d['slots'] as $s)<label><input type="radio" name="visit_at" value="{{ $s['at'] }}" required @checked(old('visit_at') === $s['at'])><span>{{ $s['label'] }}</span></label>@endforeach</div>
            </div>
        @endforeach
    </div>
@endif
