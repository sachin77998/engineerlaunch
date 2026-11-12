<section class="cp-sec cp-band" style="display:grid;grid-template-columns:minmax(0,1fr) minmax(0,1.6fr);gap:20px;align-items:center">
    <div><div style="font-size:44px">🏘️</div><h2>{{ $options['title'] }}</h2><div class="sub">Owner, dealer or builder</div></div>
    <div class="cp-box" style="display:grid;gap:4px">
        @foreach($rows as $r)
            <a href="{{ $r['url'] }}" style="display:flex;align-items:center;gap:14px;padding:10px 4px;text-decoration:none;color:inherit;border-bottom:1px solid var(--pr-line)">
                <span style="font-size:28px">{{ $r['icon'] }}</span><span style="flex:1"><b>{{ $r['label'] }}</b><br><small class="pr-mute">{{ number_format($r['count']) }} {{ $r['count'] === 1 ? 'property' : 'properties' }}</small></span><span aria-hidden="true">→</span>
            </a>
        @endforeach
    </div>
</section>
