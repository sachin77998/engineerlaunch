<section class="cp-sec">
    @include('properties.city._head', ['title' => $options['title'], 'sub' => $personal ? 'Based on what you viewed in ' . $context->place() : 'Newest in ' . $context->place(), 'more' => $context->search('buy')])
    @include('properties.city._rail', ['slot' => $items->map(fn ($p) => view('properties.city._listing', ['p' => $p, 'shortlisted' => $shortlisted ?? []])->render())->implode('')])
</section>
