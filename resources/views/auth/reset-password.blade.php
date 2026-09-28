@extends('layouts.site')
@section('title','Reset password')
@section('content')
<div class="container" style="max-width:520px;padding:40px 16px">
<form class="panel" method="post" action="{{route('password.update')}}">@csrf
<h1>Set a new password</h1>
<input type="hidden" name="token" value="{{$token}}">
@foreach($errors->all() as $error)<p class="error" role="alert">{{$error}}</p>@endforeach
<label class="label" for="reset-email">Email address</label><input class="field" id="reset-email" name="email" type="email" autocomplete="email" value="{{old('email',$email)}}" required>
<label class="label" for="new-password">New password</label><input class="field" id="new-password" name="password" type="password" autocomplete="new-password" minlength="8" required>
<label class="label" for="confirm-password">Confirm new password</label><input class="field" id="confirm-password" name="password_confirmation" type="password" autocomplete="new-password" minlength="8" required>
<button class="primary" style="margin-top:20px" type="submit">Reset password</button>
<p><a href="{{route('password.request')}}">Request a new reset link</a></p>
</form></div>
@endsection
