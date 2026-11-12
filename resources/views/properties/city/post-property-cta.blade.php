@if(!empty($options['compact']))
    <div class="cp-box" style="background:linear-gradient(140deg,#ecfdf5,#e0e7ff);display:grid;gap:8px">
        <b style="font-size:18px">Own a property here?</b><span class="pr-mute">Post a free ad — no brokerage.</span>
        <a class="pr-btn" href="{{ route('properties.post') }}">Post property — free</a>
    </div>
@else
    <section class="cp-cta">
        <div><h2>Selling or renting out?</h2><span class="pr-mute">Post a free ad and talk to buyers and tenants directly.</span></div>
        <div style="display:grid;gap:8px;justify-items:start">
            <a class="pr-btn" href="{{ route('properties.post') }}">Post property — it's free</a>
            @if($whatsapp)<a href="{{ $whatsapp }}" target="_blank" rel="noopener" style="font-weight:700">Post via WhatsApp →</a>@endif
        </div>
    </section>
@endif
