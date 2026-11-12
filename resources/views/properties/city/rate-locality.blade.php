<section class="cp-sec cp-band">
    <form method="get" action="{{ route('real-estate.rate', $context->slug()) }}" style="display:flex;gap:16px;align-items:center;flex-wrap:wrap;justify-content:center;text-align:center">
        <div style="font-size:44px">⭐</div>
        <div style="display:grid;gap:10px;justify-items:center">
            <h2>{{ $options['title'] }}</h2>
            <select name="locality" required aria-label="Your locality" style="padding:9px 12px;border-radius:10px;border:1px solid var(--pr-line);min-width:240px">
                <option value="">Choose your locality</option>@foreach($localities as $l)<option value="{{ $l }}">{{ $l }}</option>@endforeach
            </select>
            <span class="cp-stars" role="group" aria-label="Rating">@for($s = 5; $s >= 1; $s--)<button name="stars" value="{{ $s }}" title="{{ $s }} out of 5" aria-label="{{ $s }} stars">★</button>@endfor</span>
        </div>
    </form>
</section>
