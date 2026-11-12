<section class="cp-sec">
    @include('properties.city._head', ['title' => $options['title'], 'sub' => 'in ' . $context->place()])
    @php($slot = '')
    @foreach($items as $row)
        @php($slot .= view('properties.city._builder', $row)->render())
    @endforeach
    @include('properties.city._rail', ['slot' => $slot])
</section>
