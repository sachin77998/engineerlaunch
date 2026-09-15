@extends('admin.industrial.layout')
@section('manager')
<h2>{{ $item->exists ? 'Edit' : 'Add' }} {{ $labels[$type] }}</h2>
<p class="im-note">Use source evidence for the specific location or opening. Setting a verification status requires a source name, source URL and last-verified date. Company occupancy is public only when active and verified using an official, company or admin source.</p>
<form id="im-record" class="im-panel im-form" method="post" action="{{ $item->exists ? route('admin.industrial.update', [$type,$item->id]) : route('admin.industrial.store', $type) }}">@csrf @if($item->exists) @method('put') @endif
<div class="im-grid">@foreach($fields as $field=>$input)
@php($label = ['state_slug'=>'State','area_slug'=>'Industrial area','company_slug'=>'Company / Facility','department_slug'=>'Department','role_slug'=>'Job role','slug'=>'URL slug','external_id'=>'External opening reference','is_active'=>'Active','is_verified'=>'Verified','is_featured'=>'Featured hub'][$field] ?? ucwords(str_replace('_',' ',$field)))
<div class="{{ $input==='textarea' ? 'wide' : '' }}"><label for="im-{{ $field }}">{{ $label }}</label>
@if($input==='select')<select class="field" id="im-{{ $field }}" name="{{ $field }}"><option value="">Select {{ $label }}</option>@foreach($options[$field] ?? [] as $value=>$text)<option value="{{ $value }}" @selected((string)($values[$field] ?? '')===(string)$value)>{{ $text }}</option>@endforeach</select>
@elseif($input==='textarea')<textarea class="field" id="im-{{ $field }}" name="{{ $field }}" rows="6">{{ $values[$field] ?? '' }}</textarea>
@elseif($input==='checkbox')<input type="hidden" name="{{ $field }}" value="0"><input id="im-{{ $field }}" type="checkbox" name="{{ $field }}" value="1" @checked($values[$field] ?? false)>
@else<input class="field" id="im-{{ $field }}" type="{{ $input }}" name="{{ $field }}" value="{{ $values[$field] ?? '' }}" @if($input==='number') step="any" @endif @if(in_array($field,['name','job_title','external_id'])) required @endif>
@endif
@if(in_array($field,['sectors','skills']))<small>Separate values with semicolons, for example CNC;VMC;Maintenance.</small>@endif
@if($input==='datetime-local')<small>Times use {{ config('app.timezone') }}.</small>@endif
@if(in_array($field,['salary_min','salary_max']))<small>Amount in INR for the selected salary period.</small>@endif
@if($field==='slug')<small>Leave blank to generate from the name. Use a distinct facility suffix when multiple plants share a name.</small>@endif
@if($field==='is_active')<small>Deactivate to remove this record and dependent records from public discovery without deleting them.</small>@endif
</div>@endforeach</div><p id="im-lookup-status" role="status"></p><button class="primary" type="submit">Save record</button></form>
@endsection
@push('scripts')
<script>
(()=>{const form=document.getElementById('im-record'),status=document.getElementById('im-lookup-status'),url=@json(route('admin.industrial.lookups'));let controller,sequence=0;const get=name=>form.querySelector('[name="'+name+'"]');
async function refresh(name){if(controller)controller.abort();controller=new AbortController();const current=++sequence;const children=name==='state_slug'?['area_slug','company_slug']:name==='area_slug'?['company_slug']:['role_slug'];for(const key of children){const field=get(key);if(field){field.replaceChildren(new Option('Select an option',''));field.disabled=true;}}status.textContent='Loading available records...';
try{const query=new URLSearchParams();for(const key of ['state_slug','area_slug','department_slug'])if(get(key)?.value)query.set(key,get(key).value);const response=await fetch(url+'?'+query,{headers:{Accept:'application/json'},signal:controller.signal});if(!response.ok)throw Error('Lookup failed');const options=await response.json();if(current!==sequence)return;for(const key of children){const field=get(key);if(!field)continue;for(const [value,label] of Object.entries(options[key]||{}))field.add(new Option(label,value));field.disabled=false;}status.textContent='';}
catch(error){if(error.name!=='AbortError')status.textContent='Unable to load selections. Change the parent selection to retry.';}}
for(const name of ['state_slug','area_slug','department_slug'])get(name)?.addEventListener('change',()=>refresh(name));})();
</script>
@endpush
