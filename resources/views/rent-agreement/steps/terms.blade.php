@php($t = old('terms', $a->terms ?? []))
<form method="post" action="{{ route('rent-agreement.save', [$a, 'terms']) }}" style="display:grid;gap:16px">
    @csrf
    <div class="hl-two">
        <div><label class="f" for="t-start">Agreement starts on <em>*</em></label><input id="t-start" type="date" name="start_date" required value="{{ old('start_date', $a->start_date?->toDateString() ?? now()->addDays(3)->toDateString()) }}"></div>
        <div><label class="f" for="t-months">Duration (months) <em>*</em></label><input id="t-months" type="number" name="months" min="1" max="{{ $o['max_months'] }}" required value="{{ old('months', $a->months) }}"></div>
        <div><label class="f" for="t-rent">Monthly rent (₹) <em>*</em></label><input id="t-rent" type="number" name="rent" min="500" required value="{{ old('rent', $a->rent) }}"></div>
        <div><label class="f" for="t-dep">Security deposit (₹) <em>*</em></label><input id="t-dep" type="number" name="deposit" min="0" required value="{{ old('deposit', $a->deposit) }}"></div>
        <div><label class="f" for="t-due">Rent due by (day of month)</label><input id="t-due" type="number" name="terms[due_day]" min="1" max="28" required value="{{ $t['due_day'] ?? 5 }}"></div>
        <div><label class="f" for="t-esc">Rent increase on renewal (%)</label><input id="t-esc" type="number" step="0.5" name="terms[escalation_pct]" min="0" max="50" required value="{{ $t['escalation_pct'] ?? 5 }}"></div>
        <div><label class="f" for="t-notice">Notice period (months)</label><input id="t-notice" type="number" name="terms[notice_months]" min="0" max="12" required value="{{ $t['notice_months'] ?? 1 }}"></div>
        <div><label class="f" for="t-lock">Lock-in (months)</label><input id="t-lock" type="number" name="terms[lockin_months]" min="0" max="{{ $o['max_months'] }}" required value="{{ $t['lockin_months'] ?? 0 }}"></div>
    </div>
    <div><label class="f">Maintenance charges</label>
        <div class="hl-pills">@foreach(['included' => 'Included in rent', 'tenant' => 'Paid by tenant'] as $k => $label)<label><input type="radio" name="terms[maintenance]" value="{{ $k }}" @checked(($t['maintenance'] ?? 'included') === $k)><span>{{ $label }}</span></label>@endforeach</div>
        <input type="number" name="terms[maintenance_amount]" min="0" value="{{ $t['maintenance_amount'] ?? '' }}" placeholder="Monthly maintenance amount (₹), if paid by tenant" style="margin-top:8px"></div>
    <label style="display:flex;gap:8px;align-items:center"><input type="checkbox" name="terms[pets]" value="1" @checked($t['pets'] ?? false)> Pets allowed</label>
    <div><label class="f" for="t-extra">Additional clauses</label><textarea id="t-extra" name="terms[extra]" rows="4" maxlength="3000" placeholder="One clause per line, e.g. Tenant will not make structural changes.">{{ $t['extra'] ?? '' }}</textarea></div>
    <div class="hl-bar"><a class="pr-btn ghost" style="min-width:0;background:#fff;color:#0f766e" href="{{ route('rent-agreement.step', [$a, 'property']) }}">Back</a><button class="pr-btn">Review</button></div>
</form>
