@extends('layouts.site')
@section('title', 'DLF Cyber City companies and careers')
@push('styles')
<style>
.cyber-page{padding:42px 0}.cyber-grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(min(100%,280px),1fr));gap:20px;margin:28px 0}.cyber-card{position:relative;border:1px solid #dbe4f2;border-radius:16px;padding:24px;background:var(--surface,#fff);color:var(--text,#172a45)}.cyber-card h2{font-size:23px}.cyber-card a{color:#2463cc}.cyber-main{color:inherit!important;text-decoration:none}.cyber-main:after{content:"";position:absolute;inset:0;border-radius:16px}.cyber-main:focus-visible:after{outline:3px solid #2463cc;outline-offset:3px}.cyber-action{position:relative;z-index:1;display:inline-block;margin:8px 14px 0 0}.cyber-card:hover{border-color:#2463cc}.cyber-note{max-width:850px;line-height:1.7}
</style>
@endpush
@section('content')
<main class="container cyber-page">
    <a href="{{route('companies.index')}}">All companies</a>
    <h1>Companies in DLF Cyber City</h1>
    <p class="cyber-note">Explore companies with offices in Cyber City, Gurugram. Each listing links to its official location information and careers page. Check the individual vacancy for its exact workplace; a company office here does not mean every opening is based here.</p>
    <p>Location sources reviewed {{config('cyber_city.reviewed_on')}}.</p>
    <a href="{{url('/')}}?location=Gurugram#jobs">Search current Gurugram jobs ?</a>
    <div class="cyber-grid">
    @foreach(config('cyber_city.companies', []) as $entry)
        @php($company = $companies->get($entry['name']))
        <article class="cyber-card">
            <h2><a class="cyber-main" href="{{$entry['careers_url']}}" target="_blank" rel="noopener noreferrer">{{$entry['name']}}</a></h2>
            <p>{{$entry['industry']}}</p><p>{{$entry['location']}}</p>
            <p>Explore official careers ?</p>
            @if($company)
                <a class="cyber-action" href="{{route('companies.show',$company)}}">{{number_format($company->active_jobs_count)}} openings stored across all locations</a>
            @endif
            <a class="cyber-action" href="{{$entry['source_url']}}" target="_blank" rel="noopener noreferrer">Official location source ?</a>
        </article>
    @endforeach
    </div>
</main>
@endsection
