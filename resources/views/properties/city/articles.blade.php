<section class="cp-sec" data-tabs>
    <div class="cp-sec-head"><h2>{{ $options['title'] }}</h2><a href="{{ route('news.index') }}">All property news →</a></div>
    <div class="cp-tabbar">@foreach($tabs->keys() as $i => $label)<button type="button" data-tab="a{{ $i }}" class="{{ $i === 0 ? 'on' : '' }}">{{ $label }}</button>@endforeach</div>
    @foreach($tabs->values() as $i => $list)
        <div data-pane="a{{ $i }}" @if($i > 0) hidden @endif>
            @php($slot = '')
            @foreach($list as $a)
                @php($slot .= view('properties.city._article', ['a' => $a])->render())
            @endforeach
            @include('properties.city._rail', ['slot' => $slot])
        </div>
    @endforeach
</section>
