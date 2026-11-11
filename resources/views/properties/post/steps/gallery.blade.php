@php
    $m = config('properties.media');
    $photos = $p->media->where('type', 'photo')->values();
    $videos = $p->media->where('type', 'video')->values();
@endphp
<form method="post" action="{{ route('properties.upload', $p->id) }}" enctype="multipart/form-data" style="border:2px dashed #c7d2fe;border-radius:14px;padding:22px;text-align:center;background:#fafaff">
    @csrf
    <div style="font-size:30px">▣</div>
    <p style="margin:6px 0 14px"><b>Add photos and a video.</b> <span class="pr-mute">Listings with photos get far more enquiries.</span></p>
    <div style="display:flex;gap:16px;justify-content:center;flex-wrap:wrap;text-align:left">
        <label class="f">Photos <span class="pr-mute" style="font-weight:500">(JPG, PNG or WebP, up to {{ $m['photos'] }}, {{ intdiv($m['photo_kb'], 1024) }} MB each)</span>
            <input type="file" name="photos[]" accept="image/jpeg,image/png,image/webp" multiple style="display:block;margin-top:6px"></label>
        <label class="f">Video <span class="pr-mute" style="font-weight:500">(MP4, WebM or MOV, up to {{ $m['videos'] }}, {{ intdiv($m['video_kb'], 1024) }} MB each)</span>
            <input type="file" name="videos[]" accept="video/mp4,video/webm,video/quicktime" multiple style="display:block;margin-top:6px"></label>
    </div>
    <button class="pr-btn" type="submit" style="margin-top:14px">Upload</button>
    @if($errors->has('photos.*') || $errors->has('videos.*'))<div class="pr-err">{{ $errors->first('photos.*') ?: $errors->first('videos.*') }}</div>@endif
</form>
@if($photos->isNotEmpty() || $videos->isNotEmpty())
    <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(170px,1fr));gap:12px;margin-top:18px">
        @foreach($photos->concat($videos) as $n => $media)
            <div style="border:1px solid var(--pr-line);border-radius:12px;overflow:hidden;background:#fff">
                @if($media->type === 'photo')<img src="{{ $media->url() }}" alt="" style="width:100%;height:120px;object-fit:cover;display:block">@else<video src="{{ $media->url() }}" style="width:100%;height:120px;object-fit:cover;display:block;background:#000" preload="metadata"></video>@endif
                <div style="display:flex;gap:6px;padding:8px;align-items:center">
                    @if($media->type === 'photo' && $n === 0)<span class="pr-tag ok">Cover</span>
                    @elseif($media->type === 'photo')<form method="post" action="{{ route('properties.media', [$p->id, $media->id]) }}">@csrf<input type="hidden" name="action" value="cover"><button class="pr-btn ghost sm" type="submit">Make cover</button></form>@else<span class="pr-tag">Video</span>@endif
                    <form method="post" action="{{ route('properties.media', [$p->id, $media->id]) }}" style="margin-left:auto" onsubmit="return confirm('Remove this file?')">@csrf<input type="hidden" name="action" value="delete"><button class="pr-btn danger sm" type="submit" aria-label="Remove">✕</button></form>
                </div>
            </div>
        @endforeach
    </div>
@endif
