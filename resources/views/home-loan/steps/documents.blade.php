<p class="pr-mute" style="margin-top:-4px">PDF, JPG or PNG up to {{ round($o['doc_max_kb'] / 1024) }} MB each. Documents are private — only you and our loan desk can open them. You can also submit now and share them later.</p>
@foreach($o['documents'] as $kind => [$label, $for])
    @continue($for && $app->employment && $for !== $app->employment)
    <div class="hl-doc">
        <div><b>{{ $label }}</b>
            @if(isset($docs[$kind]))<ul>@foreach($docs[$kind] as $doc)<li>📄 <a href="{{ route('home-loan.document', $doc) }}">{{ \Illuminate\Support\Str::limit($doc->original_name, 40) }}</a> <span class="pr-mute">{{ round($doc->size / 1024) }} KB</span>
                <form method="post" action="{{ route('home-loan.document.remove', $doc) }}" style="display:inline" onsubmit="return confirm('Remove this document?')">@csrf<button style="border:0;background:none;color:#b91c1c;cursor:pointer">Remove</button></form></li>@endforeach</ul>@endif
        </div>
        <form method="post" action="{{ route('home-loan.upload', $app) }}" enctype="multipart/form-data">
            @csrf<input type="hidden" name="kind" value="{{ $kind }}">
            <label class="pr-btn ghost sm" style="cursor:pointer">{{ isset($docs[$kind]) ? 'Add more' : 'Upload' }}<input type="file" name="file" accept=".pdf,.jpg,.jpeg,.png" hidden onchange="this.form.submit()"></label>
        </form>
    </div>
@endforeach
<form method="post" action="{{ route('home-loan.save', [$app, 'documents']) }}">
    @csrf
    <div class="hl-bar"><a class="pr-btn ghost" style="min-width:0;background:#fff;color:#0f766e" href="{{ route('home-loan.step', [$app, 'property']) }}">Back</a><button class="pr-btn">{{ $app->submitted_at ? 'Save' : 'Submit application' }}</button></div>
</form>
