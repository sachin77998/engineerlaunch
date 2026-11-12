<section class="cp-sec">
    @include('properties.city._head', ['title' => $options['title'], 'sub' => ($options['sub'] ?? '') . ' · ' . $context->place(), 'more' => route('projects.index', $context->slug())])
    @include('properties.city._rail', ['slot' => $items->map(fn ($pj) => view('properties.city._project', ['pj' => $pj])->render())->implode('')])
</section>
