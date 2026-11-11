{{-- Equity funding rounds from funding news, with their investors. --}}
<section class="ci-card" id="equity-funding">
    <h2>Funding rounds</h2>
    <div class="ci-table-wrap"><table class="ci-table ci-kv">
        <thead><tr><th>Date</th><th>Round</th><th>Amount</th><th>Investors</th></tr></thead>
        <tbody>
        @foreach($equityRounds as $r)
            <tr><td>{{ $r->announced_on->format('d M Y') }}</td><td>{{ $r->label() }}</td>
                <td>@if($r->source_url)<a href="{{ $r->source_url }}" target="_blank" rel="noopener nofollow" title="{{ $r->headline }}">{{ $r->amountLabel() ?? 'Undisclosed' }}</a>@else{{ $r->amountLabel() ?? 'Undisclosed' }}@endif</td>
                <td>@include('funding._investors')</td></tr>
        @endforeach
        </tbody>
    </table></div>
    <p class="ci-muted" style="margin:10px 0 0">@if($equityRounds->whereNotNull('amount_usd')->isNotEmpty())Total disclosed ~${{ number_format($equityRounds->sum('amount_usd') / 1e6, 1) }} mn · @endif<a href="{{ route('funding.rounds') }}">All funding rounds</a></p>
</section>
