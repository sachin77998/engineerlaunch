@extends('layouts.app')
@section('title','Owner Analytics — Ascendia')
@section('content')
<section class="hero-band"><div class="container"><h1 class="fw-bold">Candidate & Hiring Analytics</h1><p class="mb-0">Live database aggregates for applications, cities and expected salary.</p></div></section>
<div class="container py-4">
 <form class="card card-body shadow-sm mb-4" method="get">
  <div class="row g-3 align-items-end">
   <div class="col-md-4"><label class="form-label">Candidate city</label><select class="form-select" name="city"><option value="">All cities</option>@foreach(['Mohali','Chandigarh','Pune','Delhi','Gurugram','Noida'] as $city)<option @selected(request('city')===$city)>{{$city}}</option>@endforeach</select></div>
   <div class="col-md-3"><label class="form-label">Minimum salary</label><input class="form-control" type="number" name="salary_min" value="{{request('salary_min')}}" placeholder="500000"></div>
   <div class="col-md-3"><label class="form-label">Maximum salary</label><input class="form-control" type="number" name="salary_max" value="{{request('salary_max')}}" placeholder="1000000"></div>
   <div class="col-md-2 d-grid"><button class="btn btn-primary">Apply filters</button></div>
  </div>
 </form>
 <div class="row g-3 mb-4">
  @foreach(['students'=>'Students','hr_accounts'=>'HR accounts','applications'=>'Filtered applications','new_applications'=>'New applications'] as $key=>$label)<div class="col-md-3"><div class="card card-body h-100 shadow-sm"><span class="text-secondary">{{$label}}</span><strong class="display-6">{{number_format($analytics[$key])}}</strong></div></div>@endforeach
 </div>
 <div class="row g-4">
  <div class="col-lg-6"><div class="card shadow-sm h-100"><div class="card-header fw-bold">Applications by city</div><div class="card-body"><table class="table align-middle"><thead><tr><th>City</th><th>Applications</th></tr></thead><tbody>@foreach($analytics['city_counts'] as $city=>$count)<tr><td>{{$city}}</td><td><span class="badge text-bg-primary">{{$count}}</span></td></tr>@endforeach</tbody></table></div></div></div>
  <div class="col-lg-6"><div class="card shadow-sm h-100"><div class="card-header fw-bold">Applications by expected salary</div><div class="card-body"><table class="table align-middle"><thead><tr><th>Salary band</th><th>Applications</th></tr></thead><tbody>@foreach($analytics['salary_counts'] as $band=>$count)<tr><td>{{$band}}</td><td><span class="badge text-bg-success">{{$count}}</span></td></tr>@endforeach</tbody></table></div></div></div>
  <div class="col-12"><div class="card shadow-sm"><div class="card-header fw-bold">Expected salary aggregate functions</div><div class="card-body row g-3">@foreach(['candidates'=>'COUNT','total'=>'SUM','average'=>'AVG','minimum'=>'MIN','maximum'=>'MAX'] as $field=>$label)<div class="col"><small class="text-secondary d-block">{{$label}}</small><strong>{{in_array($field,['candidates'])?number_format($analytics['salary_statistics']->$field??0):'₹'.number_format($analytics['salary_statistics']->$field??0)}}</strong></div>@endforeach</div></div></div>
 </div>
</div>
@endsection
