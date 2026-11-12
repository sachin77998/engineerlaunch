@extends('layouts.site')
@section('title', 'Property option lists — admin')
@section('content')
@include('properties._nav')
<div class="pr"><div class="pr-wrap">
    @include('properties.admin._tabs')
    <div style="display:grid;grid-template-columns:260px minmax(0,1fr);gap:16px;align-items:start">
        <nav class="pr-card-box" style="padding:8px;max-height:75vh;overflow:auto">
            @foreach($groups as $key => $def)<a href="{{ route('admin.property-options.index', ['group' => $key]) }}" style="display:block;padding:7px 10px;border-radius:8px;text-decoration:none;{{ $key === $group ? 'background:var(--pr-soft);font-weight:700' : 'color:#374151' }}">{{ $def[0] }}</a>@endforeach
        </nav>
        <div class="pr-card-box">
            <h1 style="margin-top:0;font-size:22px">{{ $groups[$group][0] }}</h1>
            <p class="pr-mute" style="margin-top:0">Table <code>{{ \App\Models\Property\PropertyOption::tableFor($group) }}</code>. @if($groups[$group][1] === 'list')“Extra” holds the other values as a JSON list, e.g. <code>["🛎️"]</code>.@endif Keys are stored on listings, so they can't be renamed — switch a row off instead.</p>
            <form method="post" action="{{ route('admin.property-options.save', $group) }}">@csrf
                <div style="overflow-x:auto"><table style="width:100%;border-collapse:collapse;font-size:14px">
                    <thead><tr style="text-align:left"><th style="padding:6px">Key</th><th style="padding:6px">Label</th><th style="padding:6px">Extra</th><th style="padding:6px;width:80px">Order</th><th style="padding:6px">On</th></tr></thead>
                    <tbody>
                    @foreach($rows as $r)
                        <tr style="border-top:1px solid var(--pr-line)">
                            <td style="padding:6px"><code>{{ $r->key }}</code></td>
                            <td style="padding:6px"><input name="rows[{{ $r->id }}][label]" value="{{ $r->label }}" maxlength="160" required></td>
                            <td style="padding:6px"><input name="rows[{{ $r->id }}][extra]" value="{{ $r->extra ? json_encode($r->extra, JSON_UNESCAPED_UNICODE) : '' }}" maxlength="1000" placeholder="—"></td>
                            <td style="padding:6px"><input type="number" name="rows[{{ $r->id }}][sort]" value="{{ $r->sort }}" min="0"></td>
                            <td style="padding:6px"><input type="checkbox" name="rows[{{ $r->id }}][is_active]" value="1" @checked($r->is_active) aria-label="Active"></td>
                        </tr>
                    @endforeach
                    <tr style="border-top:2px solid var(--pr-line);background:#f9fafb">
                        <td style="padding:6px"><input name="new[key]" maxlength="60" placeholder="new_key" pattern="[a-z0-9_]+"></td>
                        <td style="padding:6px"><input name="new[label]" maxlength="160" placeholder="Add a new option"></td>
                        <td style="padding:6px"><input name="new[extra]" maxlength="1000" placeholder="[]"></td><td colspan="2"></td>
                    </tr>
                    </tbody>
                </table></div>
                <button class="pr-btn" style="margin-top:12px">Save</button>
            </form>
        </div>
    </div>
</div></div>
@endsection
