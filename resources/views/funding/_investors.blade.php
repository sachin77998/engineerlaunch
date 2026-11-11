{{-- A round's investors, leads first and marked. --}}
@forelse($r->investors as $inv)<a href="{{ route('funding.investor', $inv->slug) }}">{{ $inv->name }}</a>@if($inv->pivot->is_lead)<span class="ex-badge">Lead</span>@endif @if(!$loop->last) · @endif @empty<span style="color:#5b6b82">Not disclosed</span>@endforelse
