@extends('layouts.site')
@section('title', 'Trading settings — Back office')
@push('styles')@include('trading.partials.styles')@endpush
@section('content')
@include('trading.partials.hero', ['title' => 'Fees, cut-offs and holidays'])
<main class="dt-wrap dt-main">
    <section class="dt-card">
        <h2>Settings</h2>
        <form method="post" action="{{ route('trading.admin.settings.save') }}">@csrf
            <div class="dt-form">
                @foreach($defaults as $key => [$default, $label, $type])
                    <label>{{ $label }}<input name="settings[{{ $key }}]" value="{{ old('settings.' . $key, $values[$key] ?? $default) }}" @if($type === 'time') placeholder="HH:MM" @endif></label>
                @endforeach
            </div>
            <button class="dt-btn gold" type="submit" style="margin-top:12px">Save settings</button>
        </form>
    </section>
    <section class="dt-card">
        <h2>Market holidays</h2>
        <form method="post" action="{{ route('trading.admin.holidays.add') }}" class="dt-filters">@csrf
            <input type="date" name="holiday_on" required><input name="description" placeholder="Holiday name" required><button class="dt-btn" type="submit">Add holiday</button>
        </form>
        <div class="dt-table-wrap" style="margin-top:12px"><table class="dt-table"><thead><tr><th>Date</th><th>Day</th><th>Holiday</th><th>Source</th></tr></thead><tbody>
            @forelse($holidays as $h)<tr><td>{{ $h->holiday_on->format('d M Y') }}</td><td>{{ $h->holiday_on->format('l') }}</td><td>{{ $h->description }}</td><td>{{ $h->source }}</td></tr>
            @empty<tr><td colspan="4">No holidays loaded. Use “Refresh NAVs, holidays and margins” in the back office.</td></tr>@endforelse
        </tbody></table></div>
    </section>
</main>
@endsection
