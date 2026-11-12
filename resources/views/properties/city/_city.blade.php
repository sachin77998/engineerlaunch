@php($img = $city->image_path ? \Illuminate\Support\Facades\Storage::disk('public')->url($city->image_path) : $city->image_url)
<a class="cp-city" href="{{ route('real-estate.city', $city->slug) }}">
    <span class="ph" @if($img) style="background-image:url('{{ $img }}')" @endif>@unless($img)🏙️@endunless</span>
    <span><b style="font-size:17px">{{ $city->name }}</b><br><small class="pr-mute">{{ $count ? number_format($count) . ' ' . ($count === 1 ? 'property' : 'properties') : 'Explore' }}</small></span>
</a>
