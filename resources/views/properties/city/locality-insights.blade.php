<section class="cp-sec">
    @include('properties.city._head', ['title' => $options['title'], 'sub' => 'Asking prices, ratings and reviews in ' . $context->place()])
    @php($slot = '')
    @foreach($items as $l)
        @php($slot .= view('properties.city._insight', ['l' => $l])->render())
    @endforeach
    @include('properties.city._rail', ['slot' => $slot])
</section>
