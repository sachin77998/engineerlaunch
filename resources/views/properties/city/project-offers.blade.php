<section class="cp-sec">
    @include('properties.city._head', ['title' => $options['title'], 'sub' => 'Projects with ongoing offers in ' . $context->place()])
    @include('properties.city._rail', ['slot' => $items->map(fn ($pj) => view('properties.city._project', ['pj' => $pj, 'offers' => true])->render())->implode('')])
</section>
