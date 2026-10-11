{{-- Plant department flow (gate → stores → furnace → production → quality → dispatch) for manufacturing sectors. --}}
@if(!empty($flow))
    <div class="sd-flow">
        @foreach($flow as $stage)
            <div class="sd-stage"><b>{{ $stage['name'] }}</b><small>{{ implode(' · ', $stage['departments']) }}</small>
                @foreach($stage['roles'] as $role)<a href="{{ ($sector ? route('sectors.show', [$sector->slug, 'role' => $role] + \Illuminate\Support\Arr::except($filters, 'role')) : route('sectors.index', ['role' => $role])) }}" class="{{ ($filters['role'] ?? '') === $role ? 'on' : '' }}">{{ $role }}</a>@endforeach
            </div>
        @endforeach
    </div>
@endif
