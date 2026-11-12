<section class="cp-sec">
    @include('properties.city._head', ['title' => $options['title'], 'sub' => 'Projects by possession date'])
    <div class="cp-grid">
        @foreach($buckets as $i => $b)
            <a class="cp-tile" href="{{ $b['url'] }}" style="background:{{ ['#fff7ed', '#eff6ff', '#ecfdf5', '#fdf4ff', '#f8fafc'][$i % 5] }};min-height:110px;align-content:space-between"><b style="font-size:20px">{{ $b['label'] }}</b><small>{{ number_format($b['count']) }} {{ $b['count'] === 1 ? 'project' : 'projects' }}</small></a>
        @endforeach
    </div>
</section>
