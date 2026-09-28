@extends('layouts.site')
@section('title','Forgot password')
@section('content')
<div class="container" style="max-width:520px;padding:40px 16px">
<form class="panel" method="post" action="{{route('password.email')}}">@csrf
<h1>Forgot password?</h1><p>Enter your account email to receive a reset link.</p>
@if(session('status'))<p role="status">{{session('status')}}</p>@endif
@error('email')<p class="error" role="alert">{{$message}}</p>@enderror
<label class="label" for="reset-email">Email address</label>
<input class="field" id="reset-email" name="email" type="email" autocomplete="email" value="{{old('email')}}" required>
<button class="primary" style="margin:20px 0" type="submit">Send reset link</button>
<p><a href="{{route('login')}}">Back to login</a></p>
</form></div>
@endsection
