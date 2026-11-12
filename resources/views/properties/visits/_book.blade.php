{{-- "Book a site visit" card for a listing or project. Expects $type (property|project), $slug, $visitSlots, $myVisit (open visit of this user or null). --}}
<div class="pr-card-box" id="book-visit" style="display:grid;gap:12px;scroll-margin-top:70px">
    <h3 style="margin:0">📅 Book a site visit</h3>
    @if($myVisit)
        <p style="margin:0">You have a visit on <b>{{ $myVisit->visit_at->format('D d M, g:i A') }}</b> ({{ strtolower($myVisit->statusLabel()) }}).</p>
        <a class="pr-btn ghost sm" href="{{ route('site-visits.index') }}">Change or cancel</a>
    @elseif(!auth()->check())
        <p class="pr-mute" style="margin:0">Pick a time and we'll let the {{ $type === 'project' ? 'builder' : 'owner' }} know.</p>
        <a class="pr-btn" href="{{ route('login') }}">Log in to book a visit</a>
    @else
        <form method="post" action="{{ route('site-visits.book', [$type, $slug]) }}" style="display:grid;gap:12px">@csrf
            @include('properties.visits._slot_picker', ['days' => $visitSlots, 'pickerId' => 'vs-book'])
            @if($visitSlots)
                @error('visit_at')<div class="pr-err">{{ $message }}</div>@enderror
                <div class="pr-grid" style="gap:10px">
                    <div><label class="f" for="vs-name">Name</label><input id="vs-name" name="name" required maxlength="120" value="{{ old('name', auth()->user()->name) }}"></div>
                    <div><label class="f" for="vs-phone">Mobile</label><input id="vs-phone" name="phone" required maxlength="20" inputmode="tel" value="{{ old('phone', auth()->user()->phone ?? '') }}"></div>
                    <div><label class="f" for="vs-people">People</label><select id="vs-people" name="people">@for($i = 1; $i <= config('site_visits.max_people'); $i++)<option value="{{ $i }}" @selected((int) old('people', 2) === $i)>{{ $i }}</option>@endfor</select></div>
                    <div><label class="f" for="vs-email">Email</label><input id="vs-email" type="email" name="email" maxlength="191" value="{{ old('email', auth()->user()->email) }}"></div>
                </div>
                <div><label class="f" for="vs-notes">Anything the {{ $type === 'project' ? 'builder' : 'owner' }} should know? <span class="pr-mute" style="font-weight:500">(optional)</span></label><textarea id="vs-notes" name="notes" rows="2" maxlength="500">{{ old('notes') }}</textarea></div>
                <button class="pr-btn">Request visit</button>
            @endif
        </form>
    @endif
</div>
