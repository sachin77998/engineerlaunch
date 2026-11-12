<section class="cp-sec">
    @include('properties.city._head', ['title' => $options['title'] . ' ' . $context->place(), 'sub' => 'Most searched localities in the last 90 days (' . number_format($total) . ' searches)'])
    <div class="cp-box" data-tabs>
        <div class="cp-tabbar">@foreach(array_keys($tabs) as $i => $label)<button type="button" data-tab="d{{ $i }}" class="{{ $i === 0 ? 'on' : '' }}">{{ $label }}</button>@endforeach</div>
        @foreach(array_values($tabs) as $i => $rows)
            <div data-pane="d{{ $i }}" @if($i > 0) hidden @endif style="display:grid;gap:12px">
                @foreach($rows as $r)
                    <a href="{{ $r['url'] }}" style="display:grid;gap:5px;text-decoration:none;color:inherit">
                        <span style="display:flex;justify-content:space-between;gap:8px"><b>{{ $r['name'] }}@if($r['zone']) <small class="pr-mute" style="font-weight:500">· {{ $r['zone'] }}</small>@endif</b><span>{{ $r['share'] }}% of searches</span></span>
                        <span class="cp-bar"><i style="width:{{ $r['share'] }}%"></i></span>
                    </a>
                @endforeach
            </div>
        @endforeach
    </div>
</section>
