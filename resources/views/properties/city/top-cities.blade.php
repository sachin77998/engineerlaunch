<section class="cp-sec">
    <small class="pr-mute" style="font-weight:700;letter-spacing:.06em">TOP CITIES</small>
    @include('properties.city._head', ['title' => $options['title'], 'more' => route('real-estate.index')])
    <div class="cp-grid" style="grid-template-columns:repeat(auto-fill,minmax(240px,1fr));gap:16px">
        @foreach($items as $row)
            @include('properties.city._city', $row)
        @endforeach
    </div>
</section>
