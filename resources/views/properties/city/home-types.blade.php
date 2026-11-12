<section class="cp-sec">
    @include('properties.city._head', ['title' => $options['title'], 'sub' => 'in ' . $context->place()])
    @php($slot = '')
    @foreach($tiles as $i => $t)
        @php($slot .= view('properties.city._type_tile', ['t' => $t, 'tint' => ['#fff7ed', '#eff6ff', '#ecfdf5', '#fdf4ff', '#f8fafc', '#fefce8'][$i % 6]])->render())
    @endforeach
    @include('properties.city._rail', ['slot' => $slot])
</section>
