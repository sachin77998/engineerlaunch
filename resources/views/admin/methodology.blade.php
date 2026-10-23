@extends('layouts.site')
@section('title', 'Credit methodology')
@push('styles')<style>
.mt{width:min(1280px,calc(100% - 32px));margin:24px auto 60px}.mt h1{margin:0 0 4px}.mt .sub{color:var(--muted);margin:0 0 16px}
.mt-card{background:#fff;border:1px solid var(--line);border-radius:16px;padding:18px;margin-bottom:16px;box-shadow:0 8px 24px rgba(16,33,62,.05)}.mt-card h2{margin:0 0 12px;font-size:17px}
.mt-t{width:100%;border-collapse:collapse;font-size:13px}.mt-t th{text-align:left;font-size:11.5px;text-transform:uppercase;color:var(--muted);padding:6px;border-bottom:1px solid var(--line);white-space:nowrap}.mt-t td{padding:5px 6px;border-bottom:1px solid #eef2f7;vertical-align:middle}
.mt input[type=text],.mt input[type=number],.mt select,.mt textarea{width:100%;padding:6px 8px;border:1px solid #d5deeb;border-radius:8px;font:inherit;font-size:13px;box-sizing:border-box}
.mt input.n{width:78px}.mt-btn{padding:9px 16px;border-radius:9px;border:0;background:#2563eb;color:#fff;font-weight:700;cursor:pointer}.mt-btn.ghost{background:#fff;color:#2563eb;border:1px solid #bfd3f5}.mt-btn.warn{background:#fff;color:#b42318;border:1px solid #f4c7c3}
.mt-grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(220px,1fr));gap:10px}.mt label{font-size:12px;font-weight:700;color:var(--muted);display:block;margin-bottom:3px}
.mt-kpis{display:grid;grid-template-columns:repeat(auto-fit,minmax(170px,1fr));gap:10px;margin-bottom:16px}.mt-kpi{background:#f6f9ff;border:1px solid #e3ebf8;border-radius:12px;padding:12px}.mt-kpi span{font-size:12px;color:var(--muted);font-weight:700}.mt-kpi b{display:block;font-size:20px}
.mt-chips{display:flex;flex-wrap:wrap;gap:6px}.mt-chips label{display:inline-flex;gap:5px;align-items:center;font-weight:500;color:inherit;border:1px solid #e3e9f2;border-radius:999px;padding:3px 9px;margin:0;font-size:12.5px}
details.mt-card>summary{cursor:pointer;font-weight:700;font-size:15px;list-style:none}details.mt-card>summary::after{content:' ▾';color:var(--muted)}
.mt-scroll{overflow-x:auto}.mt-ok{background:#ecfdf3;border:1px solid #abefc6;color:#067647;padding:10px 14px;border-radius:10px;margin-bottom:14px}.mt-err{background:#fef3f2;border:1px solid #fecdca;color:#b42318;padding:10px 14px;border-radius:10px;margin-bottom:14px}
</style>@endpush
@section('content')
<div class="mt">
    <h1>Credit methodology</h1>
    <p class="sub">Every value here drives the company credit assessments. Saving marks stored results for recomputation.</p>
    @if(session('status'))<div class="mt-ok">{{ session('status') }}</div>@endif
    @if($errors->any())<div class="mt-err">{{ implode(' ', $errors->all()) }}</div>@endif

    <div class="mt-kpis">
        <div class="mt-kpi"><span>Companies assessed</span><b>{{ number_format($stats['assessed']) }}</b></div>
        <div class="mt-kpi"><span>Waiting for recompute</span><b>{{ number_format($stats['stale']) }}</b></div>
        <div class="mt-kpi"><span>Last computed</span><b style="font-size:15px">{{ $stats['last'] ? \Carbon\Carbon::parse($stats['last'])->format('d M Y, h:i A') : '—' }}</b></div>
        <div class="mt-kpi"><span>Sector medians stored</span><b>{{ number_format($stats['medians']) }}</b></div>
        <div class="mt-kpi"><span>Outlook model</span><b style="font-size:15px">{{ $stats['model'] ? $stats['model']->pairs . ' pairs · AUC ' . ($stats['model']->test_auc ?? '—') : 'Not trained yet' }}</b></div>
    </div>
    <div style="display:flex;gap:8px;margin-bottom:16px;flex-wrap:wrap">
        <form method="post" action="{{ route('admin.methodology.recompute') }}">@csrf<button class="mt-btn">Recompute 200 now</button></form>
        <form method="post" action="{{ route('admin.methodology.reset') }}" onsubmit="return confirm('Replace all edited values with the defaults?')">@csrf<button class="mt-btn warn">Restore defaults</button></form>
    </div>

    <form method="post" action="{{ route('admin.methodology.settings') }}" class="mt-card">@csrf
        <h2>Score bands, short-term mapping and rules</h2>
        <div class="mt-grid" style="grid-template-columns:repeat(auto-fit,minmax(320px,1fr));align-items:start">
            <div class="mt-scroll"><table class="mt-t"><thead><tr><th>Minimum score</th><th>Long-term band</th></tr></thead><tbody>
                @php $bands = collect($settings['score_bands'] ?? [])->sortKeysDesc(); $i = 0; @endphp
                @foreach($bands as $min => $rating)<tr><td><input class="n" type="number" step="0.5" name="bands[{{ $i }}][min]" value="{{ $min }}"></td><td><select name="bands[{{ $i }}][rating]">@foreach($scale as $s)<option @selected($s === $rating)>{{ $s }}</option>@endforeach</select></td></tr>@php $i++; @endphp @endforeach
                <tr><td><input class="n" type="number" step="0.5" name="bands[{{ $i }}][min]" placeholder="add"></td><td><select name="bands[{{ $i }}][rating]">@foreach($scale as $s)<option>{{ $s }}</option>@endforeach</select></td></tr>
            </tbody></table></div>
            <div class="mt-scroll"><table class="mt-t"><thead><tr><th>Long-term</th><th>Short-term</th><th>With superior liquidity</th></tr></thead><tbody>
                @foreach($scale as $s)@php $mp = $settings['short_term_map'][$s] ?? ['A4', 'A4']; @endphp
                <tr><td><b>{{ $s }}</b></td><td><select name="short[{{ $s }}][usual]">@foreach($short as $x)<option @selected($x === $mp[0])>{{ $x }}</option>@endforeach</select></td><td><select name="short[{{ $s }}][superior]">@foreach($short as $x)<option @selected($x === $mp[1])>{{ $x }}</option>@endforeach</select></td></tr>@endforeach
            </tbody></table></div>
            <div>
                <table class="mt-t"><tbody>
                    @php $labels = ['gov_notch_51' => 'Notches: government holding ≥ 51%', 'gov_notch_75' => 'Notches: government holding ≥ 75%', 'group_support_notch' => 'Notches: demonstrated group support', 'group_support_years' => 'Group support look-back (years)',
                        'inc_cap_months' => 'INC months before cap', 'inc_cap' => 'Cap when not cooperating', 'cure_non_ig_days' => 'Curing days to non-investment grade', 'cure_ig_days' => 'Curing days to investment grade', 'post_default_cap' => 'Cap during curing', 'stale_days' => 'Recompute stored results after (days)']; @endphp
                    @foreach($labels as $k => $l)<tr><td>{{ $l }}</td><td style="width:110px">@if(in_array($k, ['inc_cap', 'post_default_cap']))<select name="overlays[{{ $k }}]">@foreach($scale as $s)<option @selected(($settings['overlays'][$k] ?? '') === $s)>{{ $s }}</option>@endforeach</select>@else<input class="n" type="number" step="1" min="0" name="overlays[{{ $k }}]" value="{{ $settings['overlays'][$k] ?? '' }}">@endif</td></tr>@endforeach
                </tbody></table>
                <h3 style="font-size:14px;margin:14px 0 6px">Default pillar weights (%)</h3>
                <div class="mt-grid" style="grid-template-columns:repeat(4,1fr)">@foreach($pillars as $p)<div><label>{{ ucfirst($p) }}</label><input type="number" step="1" name="default_weights[{{ $p }}]" value="{{ $settings['default_weights'][$p] ?? 25 }}"></div>@endforeach</div>
            </div>
        </div>
        <div style="margin-top:12px"><button class="mt-btn">Save rules</button></div>
    </form>

    <form method="post" action="{{ route('admin.methodology.ratios') }}" class="mt-card mt-scroll">@csrf
        <h2>Ratios and default bands</h2>
        <table class="mt-t"><thead><tr><th>Key</th><th>Label</th><th>Better</th><th>Pillar</th><th>Excellent</th><th>Good</th><th>Adequate</th><th>Weak</th><th>On</th></tr></thead><tbody>
        @foreach($ratios as $r)@php $b = json_decode($r->band, true); @endphp
            <tr><td><code>{{ $r->key }}</code></td><td><input type="text" name="ratios[{{ $r->id }}][label]" value="{{ $r->label }}"></td>
                <td><select name="ratios[{{ $r->id }}][direction]"><option value="up" @selected($r->direction === 'up')>Higher</option><option value="down" @selected($r->direction === 'down')>Lower</option></select></td>
                <td><select name="ratios[{{ $r->id }}][pillar]">@foreach($pillars as $p)<option @selected($p === $r->pillar)>{{ $p }}</option>@endforeach</select></td>
                @for($i = 0; $i < 4; $i++)<td><input class="n" type="number" step="any" name="ratios[{{ $r->id }}][band][{{ $i }}]" value="{{ $b[$i] ?? '' }}"></td>@endfor
                <td><input type="checkbox" name="active[{{ $r->id }}]" value="1" @checked($r->is_active)></td></tr>
        @endforeach
        </tbody></table>
        <div style="margin-top:12px"><button class="mt-btn">Save ratios</button></div>
    </form>

    <h2 style="font-size:17px;margin:20px 0 10px">Sector profiles</h2>
    @foreach($sectors as $s)
        @php $sel = json_decode((string) $s->ratios, true) ?? []; $chk = json_decode((string) $s->checks, true) ?? []; $w = json_decode((string) $s->weights, true) ?? ($settings['default_weights'] ?? []); $ov = json_decode((string) $s->overrides, true) ?? []; @endphp
        <details class="mt-card" @if($errors->any() && old('name') === $s->name) open @endif>
            <summary>{{ $s->name }} <span style="color:var(--muted);font-weight:500;font-size:13px">· {{ $s->key }} · order {{ $s->match_order }}{{ $s->is_active ? '' : ' · off' }}</span></summary>
            <form method="post" action="{{ route('admin.methodology.sector', $s->id) }}" style="margin-top:12px">@csrf
                <div class="mt-grid">
                    <div><label>Name</label><input type="text" name="name" value="{{ $s->name }}"></div>
                    <div style="grid-column:span 2"><label>Methodology reference</label><input type="text" name="criteria" value="{{ $s->criteria }}"></div>
                    <div><label>Match order (lower = first)</label><input type="number" name="match_order" value="{{ $s->match_order }}"></div>
                    <div style="grid-column:span 2"><label>Industry pattern</label><input type="text" name="match_industry" value="{{ $s->match_industry }}" placeholder="/cement/i"></div>
                    <div style="grid-column:span 2"><label>Company name pattern</label><input type="text" name="match_name" value="{{ $s->match_name }}"></div>
                    <div><label>NIC prefixes (comma separated)</label><input type="text" name="nic" value="{{ implode(', ', json_decode((string) $s->nic_prefixes, true) ?? []) }}"></div>
                    @foreach($pillars as $p)<div><label>Weight: {{ $p }} (%)</label><input type="number" step="1" name="weights[{{ $p }}]" value="{{ $w[$p] ?? 0 }}"></div>@endforeach
                </div>
                <label style="margin-top:12px">Ratios used (none ticked = manufacturing set)</label>
                <div class="mt-chips">@foreach($ratios as $r)<label><input type="checkbox" name="ratios[]" value="{{ $r->key }}" @checked(in_array($r->key, $sel, true))> {{ $r->label }}</label>@endforeach</div>
                <label style="margin-top:12px">Sector checks</label>
                <div class="mt-chips">@foreach($checks as $c)<label><input type="checkbox" name="checks[]" value="{{ $c }}" @checked(in_array($c, $chk, true))> {{ str_replace('_', ' ', $c) }}</label>@endforeach</div>
                <label style="margin-top:12px">Sector bands (replace the default band for a ratio)</label>
                <table class="mt-t" style="max-width:720px"><thead><tr><th>Ratio</th><th>Excellent</th><th>Good</th><th>Adequate</th><th>Weak</th></tr></thead><tbody>
                    @php $j = 0; @endphp
                    @foreach($ov + ['' => [null, null, null, null]] as $rk => $band)
                        <tr><td><select name="override[{{ $j }}][ratio]"><option value="">—</option>@foreach($ratios as $r)<option value="{{ $r->key }}" @selected($r->key === $rk)>{{ $r->label }}</option>@endforeach</select></td>
                            @for($i = 0; $i < 4; $i++)<td><input class="n" type="number" step="any" name="override[{{ $j }}][band][{{ $i }}]" value="{{ $band[$i] ?? '' }}"></td>@endfor</tr>
                        @php $j++; @endphp
                    @endforeach
                </tbody></table>
                <div class="mt-grid" style="margin-top:12px">
                    <div style="grid-column:span 2"><label>Notes shown with the ratios (one per line)</label><textarea name="notes" rows="2">{{ implode("\n", json_decode((string) $s->notes, true) ?? []) }}</textarea></div>
                    <div><label><input type="checkbox" name="financial_ratios" value="1" @checked($s->financial_ratios)> Financial-sector ratios (banks / NBFCs)</label><label><input type="checkbox" name="is_active" value="1" @checked($s->is_active)> Active</label></div>
                </div>
                <div style="margin-top:12px"><button class="mt-btn">Save {{ $s->name }}</button></div>
            </form>
        </details>
    @endforeach
</div>
@endsection
