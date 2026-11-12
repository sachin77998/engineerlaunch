<p class="pr-mute" style="margin-top:0">Is this unit in one of these projects? Linking it shows your listing on the project page, next to the building's photos and details.</p>
<div style="display:grid;gap:8px">
    <label class="pr-chip" style="justify-content:flex-start"><input type="radio" name="project_id" value="" @checked(!old('project_id', $p->project_id))> Not in a listed project</label>
    @foreach($projects as $pj)
        <label class="pr-chip" style="justify-content:flex-start"><input type="radio" name="project_id" value="{{ $pj->id }}" @checked((int) old('project_id', $p->project_id) === $pj->id)> {{ $pj->name }}@if($pj->locality) <span class="pr-mute">· {{ $pj->locality }}</span>@endif</label>
    @endforeach
</div>
@if($errors->has('project_id'))<div class="pr-err">{{ $errors->first('project_id') }}</div>@endif
