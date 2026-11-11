<div class="ra-person" style="background:#f8fafc">
    <h3>Have an old agreement?</h3>
    <span class="pr-mute" style="font-size:14px">Upload it (PDF or Word) and we prepare it on stamp paper with the details below — or skip this to use our template.</span>
    @if($a->draft_path)<div>📄 <a href="{{ route('rent-agreement.draft', $a) }}">{{ $a->draft_name }}</a></div>@endif
    <form method="post" action="{{ route('rent-agreement.upload', $a) }}" enctype="multipart/form-data">@csrf
        <label class="pr-btn ghost sm" style="cursor:pointer">{{ $a->draft_path ? 'Replace draft' : 'Upload draft' }}<input type="file" name="draft" accept=".pdf,.doc,.docx" hidden onchange="this.form.submit()"></label>
    </form>
</div>
<form method="post" action="{{ route('rent-agreement.save', [$a, 'parties']) }}">
    @csrf
    <div style="margin-bottom:14px"><label class="f">You are the</label>
        <div class="hl-pills">@foreach(['owner' => 'Landlord / owner', 'tenant' => 'Tenant'] as $k => $label)<label><input type="radio" name="created_by" value="{{ $k }}" required @checked(old('created_by', $a->created_by) === $k)><span>{{ $label }}</span></label>@endforeach</div></div>
    @foreach(['landlord' => 'Landlord (owner)', 'tenant' => 'Tenant'] as $p => $title)
        @php($d = old($p, $a->$p ?? []))
        <div class="ra-person">
            <h3>{{ $title }}</h3>
            <div class="hl-two">
                <div><label class="f" for="{{ $p }}-name">Full name <em>*</em></label><input id="{{ $p }}-name" type="text" name="{{ $p }}[name]" required maxlength="120" value="{{ $d['name'] ?? '' }}"></div>
                <div><label class="f" for="{{ $p }}-phone">Mobile <em>*</em></label><input id="{{ $p }}-phone" type="tel" name="{{ $p }}[phone]" required pattern="[6-9][0-9]{9}" maxlength="10" inputmode="numeric" value="{{ $d['phone'] ?? '' }}"></div>
                <div><label class="f" for="{{ $p }}-email">Email</label><input id="{{ $p }}-email" type="email" name="{{ $p }}[email]" maxlength="191" value="{{ $d['email'] ?? '' }}"></div>
                <div><label class="f" for="{{ $p }}-pan">PAN</label><input id="{{ $p }}-pan" type="text" name="{{ $p }}[pan]" maxlength="10" value="{{ $d['pan'] ?? '' }}" placeholder="Optional" style="text-transform:uppercase" oninput="this.value=this.value.toUpperCase()"></div>
            </div>
            <div><label class="f" for="{{ $p }}-addr">Permanent address <em>*</em></label><input id="{{ $p }}-addr" type="text" name="{{ $p }}[address]" required maxlength="300" value="{{ $d['address'] ?? '' }}"></div>
        </div>
    @endforeach
    @if($a->draft_path)<label style="display:flex;gap:8px;align-items:center;margin-bottom:10px"><input type="checkbox" name="use_draft" value="1" @checked($a->usesDraft())> Use my uploaded draft instead of the template (skips property and terms)</label>@endif
    <div class="hl-bar"><button class="pr-btn">Continue</button></div>
</form>
