@if(session('success'))<div class="pr-flash ok">{{ session('success') }}</div>@endif
@if($errors->any())<div class="pr-flash err">{{ $errors->first() }}</div>@endif
<nav style="display:flex;gap:8px;flex-wrap:wrap;margin:20px 0 14px">
    @foreach(['admin.properties.index' => 'Listings', 'admin.projects.index' => 'Projects', 'admin.builders.index' => 'Builders', 'admin.property-cities.index' => 'Cities & localities', 'admin.property-options.index' => 'Option lists', 'admin.localities.index' => 'Locality reviews'] as $r => $label)
        @if(Route::has($r))<a class="pr-btn {{ request()->routeIs(\Illuminate\Support\Str::beforeLast($r, '.') . '.*') ? '' : 'ghost' }} sm" href="{{ route($r) }}">{{ $label }}</a>@endif
    @endforeach
</nav>
