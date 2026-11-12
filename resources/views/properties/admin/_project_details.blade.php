{{-- Admin editors for a project's units / floor plans, highlights, videos, FAQs, charges and enquiries. Expects $p, $o. --}}
@php
    $row = 'display:flex;gap:8px;flex-wrap:wrap;align-items:end;border-top:1px solid var(--pr-line);padding:10px 0';
    $children = [
        'highlights' => ['Highlights ("why buy here")', $p->highlights, [['text', 'Highlight', 'text', 255, 'flex:1;min-width:260px']]],
        'videos' => ['Videos (YouTube or Vimeo links)', $p->videos, [['url', 'Video link', 'url', 300, 'flex:1;min-width:260px'], ['title', 'Title', 'text', 160, '']]],
        'faqs' => ['FAQs (shown before the automatic ones)', $p->faqs, [['question', 'Question', 'text', 255, 'flex:1;min-width:240px'], ['answer', 'Answer', 'textarea', 5000, 'flex:2;min-width:260px']]],
        'charges' => ['Other charges ("+ Charges")', $p->charges, [['name', 'Charge', 'text', 120, ''], ['amount', 'Amount (₹)', 'number', null, ''], ['note', 'Note', 'text', 160, '']]],
    ];
@endphp
<section class="pr-card-box" style="margin-top:16px">
    <h2 style="margin-top:0">Units &amp; floor plans ({{ $p->units->count() }})</h2>
    <p class="pr-mute" style="margin-top:0">Units with the same label form one tab on the project page, e.g. "2 BHK Apartment". Leave the price empty for "Price on request".</p>
    @foreach($p->units->push(new \App\Models\Property\ProjectUnit(['area_type' => 'super_built_up'])) as $u)
        <form method="post" enctype="multipart/form-data" action="{{ route('admin.projects.unit', array_filter([$p->id, $u->id])) }}" style="{{ $row }}">@csrf
            <div><label class="f">Label</label><input name="label" value="{{ $u->label }}" required maxlength="80" placeholder="2 BHK Apartment"></div>
            <div><label class="f">BHK</label><select name="bhk"><option value="">—</option>@foreach($o['bhk'] as $k => $label)<option value="{{ $k }}" @selected($u->bhk === $k)>{{ $label }}</option>@endforeach</select></div>
            <div><label class="f">Area type</label><select name="area_type">@foreach($o['area_types'] as $k => $label)<option value="{{ $k }}" @selected($u->area_type === $k)>{{ $label }}</option>@endforeach</select></div>
            <div style="width:110px"><label class="f">Area (sqft)</label><input type="number" step="0.01" min="1" name="area_sqft" value="{{ $u->area_sqft }}" required></div>
            <div style="width:140px"><label class="f">Price (₹)</label><input type="number" min="0" name="price" value="{{ $u->price }}"></div>
            <div style="width:80px"><label class="f">Baths</label><input type="number" min="0" name="bathrooms" value="{{ $u->bathrooms }}"></div>
            <div style="width:80px"><label class="f">Balconies</label><input type="number" min="0" name="balconies" value="{{ $u->balconies }}"></div>
            <div><label class="f">Possession</label><input type="date" name="possession_on" value="{{ $u->possession_on?->toDateString() }}"></div>
            <div><label class="f">Floor plan</label><input type="file" name="floor_plan" accept="image/jpeg,image/png,image/webp">@if($u->floorPlanUrl())<a href="{{ $u->floorPlanUrl() }}" target="_blank"><img src="{{ $u->floorPlanUrl() }}" alt="" style="height:36px;margin-top:4px"></a>@endif</div>
            <button class="pr-btn {{ $u->exists ? 'ghost' : '' }} sm">{{ $u->exists ? 'Save' : 'Add unit' }}</button>
            @if($u->exists)<button class="pr-btn danger sm" name="delete" value="1" formnovalidate onclick="return confirm('Remove this unit?')">Delete</button>@endif
        </form>
    @endforeach
</section>

@foreach($children as $child => [$title, $rows, $fields])
    <section class="pr-card-box" style="margin-top:16px">
        <h2 style="margin-top:0">{{ $title }}</h2>
        @foreach($rows->push(null) as $r)
            <form method="post" action="{{ route('admin.projects.child', array_filter([$p->id, $child, $r?->id])) }}" style="{{ $row }}">@csrf
                @foreach($fields as [$name, $label, $type, $max, $style])
                    <div style="{{ $style }}"><label class="f">{{ $label }}</label>
                        @if($type === 'textarea')<textarea name="{{ $name }}" rows="2" maxlength="{{ $max }}" required>{{ $r?->{$name} }}</textarea>
                        @else<input type="{{ $type }}" name="{{ $name }}" value="{{ $r?->{$name} }}" @if($max) maxlength="{{ $max }}" @endif @if($loop->first) required @endif @if($type === 'number') min="0" step="0.01" @endif>@endif</div>
                @endforeach
                <div style="width:70px"><label class="f">Order</label><input type="number" name="sort" min="0" value="{{ $r?->sort }}"></div>
                <button class="pr-btn {{ $r ? 'ghost' : '' }} sm">{{ $r ? 'Save' : 'Add' }}</button>
                @if($r)<button class="pr-btn danger sm" name="delete" value="1" formnovalidate>Delete</button>@endif
            </form>
        @endforeach
    </section>
@endforeach

@php($visits = \App\Models\Property\SiteVisit::where(['visitable_type' => 'project', 'visitable_id' => $p->id])->orderByRaw("status in ('requested','confirmed') desc")->orderBy('visit_at')->limit(200)->get())
<section class="pr-card-box" style="margin-top:16px">
    <h2 style="margin-top:0">Site visits ({{ $visits->count() }})</h2>
    <p class="pr-mute" style="margin-top:0">Visiting hours: {{ $p->visit_start ?: config('site_visits.project.start') }}–{{ $p->visit_end ?: config('site_visits.project.end') }}, {{ $p->visit_capacity ?: config('site_visits.project.capacity') }} per slot, {{ $p->visit_days ? collect($p->visit_days)->map(fn ($d) => ['', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'][$d])->implode(', ') : 'every day' }} — change them in the project form above.</p>
    @foreach($visits as $v)
        <div style="border-top:1px solid var(--pr-line);padding:10px 0;display:grid;gap:6px">
            <div><b>{{ $v->visit_at->format('D d M, g:i A') }}</b> · {{ $v->name }} · {{ $v->phone }} · {{ $v->people }} {{ $v->people === 1 ? 'person' : 'people' }} · {{ $v->reference() }} <span class="pr-tag">{{ $v->statusLabel() }}</span>@if($v->notes)<br><small>“{{ $v->notes }}”</small>@endif</div>
            @include('properties.visits._host_actions', ['v' => $v, 'action' => route('admin.projects.visit', [$p->id, $v->id])])
        </div>
    @endforeach
</section>

<section class="pr-card-box" style="margin-top:16px">
    <h2 style="margin-top:0">Enquiries ({{ $p->leads->count() }})</h2>
    @if($p->leads->isEmpty())<p class="pr-mute">No enquiries yet.</p>@else
        <div style="overflow-x:auto"><table style="width:100%;border-collapse:collapse;font-size:14px">
            <thead><tr style="text-align:left"><th style="padding:6px">When</th><th style="padding:6px">Type</th><th style="padding:6px">Who</th><th style="padding:6px">Unit / message</th><th style="padding:6px">Status</th></tr></thead>
            @foreach($p->leads as $l)
                <tr style="border-top:1px solid var(--pr-line)">
                    <td style="padding:6px;white-space:nowrap">{{ $l->created_at->format('d M, H:i') }}</td>
                    <td style="padding:6px">{{ \App\Models\Property\ProjectLead::KINDS[$l->kind] ?? $l->kind }}</td>
                    <td style="padding:6px">{{ $l->name ?? $l->user?->name }}<br><span class="pr-mute">{{ collect([$l->phone, $l->email ?? $l->user?->email])->filter()->implode(' · ') }}</span></td>
                    <td style="padding:6px">@if($l->unit){{ $l->unit->label }}, {{ number_format($l->unit->area_sqft) }} sqft<br>@endif{{ $l->message }}</td>
                    <td style="padding:6px"><form method="post" action="{{ route('admin.projects.lead', [$p->id, $l->id]) }}">@csrf<select name="status" onchange="this.form.submit()" aria-label="Status">@foreach(['new' => 'New', 'contacted' => 'Contacted', 'closed' => 'Closed'] as $k => $label)<option value="{{ $k }}" @selected($l->status === $k)>{{ $label }}</option>@endforeach</select></form></td>
                </tr>
            @endforeach
        </table></div>
    @endif
</section>
