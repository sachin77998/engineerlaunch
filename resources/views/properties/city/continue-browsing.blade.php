<section class="cp-sec">
    @include('properties.city._head', ['title' => $options['title'], 'sub' => 'Pick up where you left off'])
    @include('properties.city._rail', ['slot' => $items->map(fn ($p) => view('properties.city._listing', ['p' => $p, 'shortlisted' => $shortlisted ?? []])->render())->implode('')])
</section>
