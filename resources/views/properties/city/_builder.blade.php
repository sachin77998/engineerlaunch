<a class="cp-tile" href="{{ route('builders.show', $builder) }}" style="display:flex;gap:14px;align-items:center;min-width:280px">
    <span class="cp-logo">@if($builder->logoUrl())<img src="{{ $builder->logoUrl() }}" alt="{{ $builder->name }}" loading="lazy">@else{{ $builder->initials() }}@endif</span>
    <span style="display:grid;gap:3px"><b>{{ $builder->name }}</b><small>{{ $builder->projects_count }} total {{ $builder->projects_count === 1 ? 'project' : 'projects' }} | {{ $in_city }} in this city</small></span>
</a>
