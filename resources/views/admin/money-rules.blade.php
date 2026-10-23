@extends('layouts.site')
@section('title', 'Money rules')
@push('styles')<style>
.mr{width:min(1280px,calc(100% - 32px));margin:24px auto 60px}.mr h1{margin:0 0 4px}.mr .sub{color:var(--muted);margin:0 0 16px}
.mr-card{background:#fff;border:1px solid var(--line);border-radius:16px;padding:18px;margin-bottom:16px;box-shadow:0 8px 24px rgba(16,33,62,.05)}.mr-card h2{margin:0 0 12px;font-size:17px}
.mr-t{width:100%;border-collapse:collapse;font-size:13px}.mr-t th{text-align:left;font-size:11.5px;text-transform:uppercase;color:var(--muted);padding:6px;border-bottom:1px solid var(--line);white-space:nowrap}.mr-t td{padding:4px 5px;border-bottom:1px solid #eef2f7}
.mr input[type=text],.mr input[type=number],.mr select{width:100%;min-width:70px;padding:6px 7px;border:1px solid #d5deeb;border-radius:8px;font:inherit;font-size:13px;box-sizing:border-box}
.mr-btn{padding:9px 16px;border-radius:9px;border:0;background:#2563eb;color:#fff;font-weight:700;cursor:pointer}.mr-btn.warn{background:#fff;color:#b42318;border:1px solid #f4c7c3}
.mr-scroll{overflow-x:auto}.mr-new td{background:#f8fbff}.mr-grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(200px,1fr));gap:10px}.mr label{font-size:12px;font-weight:700;color:var(--muted);display:block;margin-bottom:3px}
.mr-ok{background:#ecfdf3;border:1px solid #abefc6;color:#067647;padding:10px 14px;border-radius:10px;margin-bottom:14px}.mr-err{background:#fef3f2;border:1px solid #fecdca;color:#b42318;padding:10px 14px;border-radius:10px;margin-bottom:14px}
</style>@endpush
@section('content')
<div class="mr">
    <h1>Money rules</h1>
    <p class="sub">Tax, investment and retirement-income values used by the Money Planner, tax saver and calculators. Changes apply immediately.</p>
    @if(session('status'))<div class="mr-ok">{{ session('status') }}</div>@endif
    @if($errors->any())<div class="mr-err">{{ implode(' ', $errors->all()) }}</div>@endif

    <form method="post" action="{{ route('admin.money-rules.settings') }}" class="mr-card">@csrf
        <h2>Tax year and general rules</h2>
        <div class="mr-grid">
            @foreach($settingKeys as $k => $type)
                <div><label>{{ str_replace('_', ' ', $k) }}</label><input type="{{ $type === 'num' ? 'number' : 'text' }}" step="any" name="s[{{ $k }}]" value="{{ is_array($settings[$k] ?? null) ? implode(', ', $settings[$k]) : ($settings[$k] ?? '') }}"></div>
            @endforeach
        </div>
        <h2 style="margin-top:16px">Planner defaults (pre-filled for new users)</h2>
        <div class="mr-grid">
            @foreach(($settings['defaults'] ?? []) as $k => $v)
                @continue(is_array($v))
                <div><label>{{ str_replace('_', ' ', $k) }}</label>
                    @if(is_bool($v))<select name="d[{{ $k }}]"><option value="1" @selected($v)>Yes</option><option value="0" @selected(!$v)>No</option></select>
                    @else<input type="{{ is_numeric($v) ? 'number' : 'text' }}" step="any" name="d[{{ $k }}]" value="{{ $v }}">@endif</div>
            @endforeach
        </div>
        <label style="margin-top:12px">Default monthly investments</label>
        <table class="mr-t" style="max-width:560px"><tbody>
            @foreach(array_merge($settings['defaults']['investments'] ?? [], [['type' => '', 'monthly' => '']]) as $i => $inv)
                <tr><td><select name="inv[{{ $i }}][type]"><option value="">—</option>@foreach($instruments as $key => $label)<option value="{{ $key }}" @selected($key === $inv['type'])>{{ $label }}</option>@endforeach</select></td>
                    <td style="width:140px"><input type="number" step="any" name="inv[{{ $i }}][monthly]" value="{{ $inv['monthly'] }}" placeholder="₹ / month"></td></tr>
            @endforeach
        </tbody></table>
        <div style="margin-top:12px"><button class="mr-btn">Save settings</button></div>
    </form>

    @foreach($tables as $name => $t)
        <form method="post" action="{{ route('admin.money-rules.table', $name) }}" class="mr-card mr-scroll">@csrf
            <h2>{{ $t['title'] }}</h2>
            <table class="mr-t"><thead><tr>@foreach($t['cols'] as $c)<th>{{ $c[1] }}</th>@endforeach<th>Delete</th></tr></thead><tbody>
            @foreach($t['rows']->push((object) ['id' => 'new1'])->push((object) ['id' => 'new2']) as $row)
                <tr class="{{ str_starts_with((string) $row->id, 'new') ? 'mr-new' : '' }}">
                    @foreach($t['cols'] as $c => $def)
                        @php $type = rtrim($def[0], '?'); $val = $row->$c ?? null; if ($type === 'csv' && $val) $val = implode(',', json_decode($val, true) ?? []); if (in_array($type, ['num'], true) && $val !== null) $val = rtrim(rtrim((string) $val, '0'), '.'); @endphp
                        <td>
                            @if($type === 'select')<select name="rows[{{ $row->id }}][{{ $c }}]">@if(str_starts_with((string) $row->id, 'new'))<option value="">—</option>@endif @foreach($def[2] as $o)<option @selected($o === $val)>{{ $o }}</option>@endforeach</select>
                            @elseif($type === 'bool')<input type="checkbox" name="rows[{{ $row->id }}][{{ $c }}]" value="1" @checked(str_starts_with((string) $row->id, 'new') ? true : $val)>
                            @else<input type="{{ in_array($type, ['num', 'int'], true) ? 'number' : 'text' }}" step="any" name="rows[{{ $row->id }}][{{ $c }}]" value="{{ $val }}">@endif
                        </td>
                    @endforeach
                    <td style="text-align:center">@unless(str_starts_with((string) $row->id, 'new'))<input type="checkbox" name="rows[{{ $row->id }}][_delete]" value="1">@endunless</td>
                </tr>
            @endforeach
            </tbody></table>
            <div style="margin-top:10px"><button class="mr-btn">Save {{ strtolower(strtok($t['title'], '(')) }}</button></div>
        </form>
    @endforeach

    <form method="post" action="{{ route('admin.money-rules.reset') }}" onsubmit="return confirm('Replace all money rules with the values in config/money.php?')">@csrf<button class="mr-btn warn">Restore defaults</button></form>
</div>
@endsection
