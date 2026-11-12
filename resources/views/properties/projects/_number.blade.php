{{-- "View number": revealed number (tel link), a reveal button for signed-in users, or log in. Expects $p, $phone, $revealed, $big. --}}
@if(!$phone)
    @if($big)<button type="button" class="pr-btn" data-enquire="contact">Contact builder</button>@endif
@elseif($revealed)
    <a class="pr-btn {{ $big ? '' : 'sm' }}" href="tel:{{ preg_replace('/[^\d+]/', '', $phone) }}">📞 {{ $phone }}</a>
@elseif(auth()->check())
    <button type="button" class="pr-btn {{ $big ? '' : 'sm' }}" data-reveal="{{ route('projects.phone', $p) }}">View number</button>
@else
    <a class="pr-btn {{ $big ? '' : 'sm' }}" href="{{ route('login') }}">View number</a>
@endif
