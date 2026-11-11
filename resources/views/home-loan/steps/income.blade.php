<form method="post" action="{{ route('home-loan.save', [$app, 'income']) }}" style="display:grid;gap:20px" data-hl-income>
    @csrf
    <div><label class="f">Employment type</label>
        <div class="hl-emp">@foreach($o['employment'] as $k => [$label, $icon])<label><input type="radio" name="employment" value="{{ $k }}" required @checked(old('employment', $app->employment ?? 'salaried') === $k)><span><i>{{ $icon }}</i>{{ $label }}</span></label>@endforeach</div></div>
    <div><label class="f" for="hl-inc">Monthly income (net)</label><div class="hl-in"><span>₹</span><input id="hl-inc" type="number" name="monthly_income" min="1000" required value="{{ old('monthly_income', $app->monthly_income) }}" placeholder="For example, 1,00,000" data-words></div><div class="hl-hint" data-words-out></div></div>
    <div><label class="f" for="hl-emis">Current EMIs (if any)</label><div class="hl-in"><span>₹</span><input id="hl-emis" type="number" name="current_emis" min="0" value="{{ old('current_emis', $app->current_emis ?? 0) }}" data-words></div><div class="hl-hint" data-words-out></div></div>
    <div><label class="f" for="hl-loan">Loan amount</label><div class="hl-in"><span>₹</span><input id="hl-loan" type="number" name="loan_amount" min="100000" required value="{{ old('loan_amount', $app->loan_amount) }}" placeholder="For example, 20,00,000" data-words></div><div class="hl-hint" data-words-out></div></div>
    <div><label class="f">Loan tenure (in years)</label>
        <div class="hl-pills">@foreach($o['tenures'] as $t)<label><input type="radio" name="tenure" value="{{ $t }}" required @checked((int) old('tenure', $app->tenure) === $t)><span>{{ $t }} years</span></label>@endforeach</div></div>
    <div class="hl-bar"><button class="pr-btn">Select bank</button></div>
</form>
@push('scripts')<script>
(function () {
    function words(n) {
        n = Math.round(n || 0);
        if (n >= 1e7) return '₹' + +(n / 1e7).toFixed(2) + ' Cr';
        if (n >= 1e5) return '₹' + +(n / 1e5).toFixed(2) + ' Lac';
        if (n >= 1e3) return '₹' + +(n / 1e3).toFixed(1) + ' K';
        return '₹' + n;
    }
    document.querySelectorAll('[data-words]').forEach(function (i) {
        var out = i.closest('div').parentNode.querySelector('[data-words-out]');
        var f = function () { out.textContent = words(parseFloat(i.value)); };
        i.addEventListener('input', f); f();
    });
})();
</script>@endpush
