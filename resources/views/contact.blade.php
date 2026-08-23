@extends('layouts.app')
@section('title', 'Contact Us — ' . config('platform.name'))
@section('content')
<section class="hero-band"><div class="container"><h1>Contact us</h1><p>Questions about jobs, learning, employer accounts, or the platform.</p></div></section>
<section class="section"><div class="container"><div class="panel"><h2>Talk to our team</h2><p>Email: <a href="mailto:{{ config('platform.contact.email') }}">{{ config('platform.contact.email') }}</a></p><p>Phone: <a href="tel:{{ preg_replace('/\D+/', '', config('platform.contact.phone')) }}">{{ config('platform.contact.phone') }}</a></p><p>Address: {{ config('platform.contact.address') }}</p></div></div></section>
@endsection
