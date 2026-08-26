@extends('layouts.app')

@section('title', 'Verify Email — Ascendia')

@section('content')
<style>
    .otp-page { min-height: 65vh; display: grid; place-items: center; padding: 64px 20px; background: #f5f7fb; }
    .otp-card { width: 100%; max-width: 500px; padding: 32px; background: #fff; border: 1px solid #dbe3ef; border-radius: 16px; box-shadow: 0 18px 45px rgba(15, 35, 65, .10); }
    .otp-card h1 { margin: 0 0 10px; color: #10213f; font-size: 32px; }
    .otp-card p { color: #5f6f89; line-height: 1.6; }
    .otp-input { width: 100%; padding: 14px; border: 1px solid #b7c4d8; border-radius: 8px; font-size: 24px; letter-spacing: 8px; text-align: center; }
    .otp-alert { margin: 18px 0; padding: 12px 14px; border: 1px solid #fde68a; border-radius: 8px; background: #fffbeb; color: #92400e; }
    .otp-code { display: block; margin-top: 5px; font-size: 22px; font-weight: 800; letter-spacing: 4px; }
    .otp-error { margin: 12px 0; color: #b42318; }
    .otp-submit { width: 100%; margin-top: 14px; }
</style>

<main class="otp-page">
    <form id="otp-form" class="otp-card" method="POST" action="{{ route('otp.verify') }}">
        @csrf
        <h1>Verify your email</h1>
        <p>Enter the six-digit verification code for <strong>{{ $email }}</strong>.</p>

        @if (session('mail_warning'))
            <div class="otp-alert" role="alert">
                <strong>Local testing mode</strong>
                <div>{{ session('mail_warning') }}</div>
                @if ($devOtp)
                    <span class="otp-code">{{ $devOtp }}</span>
                @endif
            </div>
        @elseif ($devOtp)
            <div class="otp-alert" role="alert">
                <strong>Local development OTP</strong>
                <span class="otp-code">{{ $devOtp }}</span>
            </div>
        @else
            <p>The code expires in 10 minutes.</p>
        @endif

        @error('code')
            <div class="otp-error" role="alert">{{ $message }}</div>
        @enderror

        <label for="code" class="form-label">Verification code</label>
        <input id="code" class="form-control otp-input" name="code" inputmode="numeric"
               autocomplete="one-time-code" pattern="[0-9]{6}" maxlength="6"
               value="{{ old('code', $devOtp ?? '') }}" required autofocus>

        <button id="otp-submit" type="submit" class="btn btn-primary otp-submit">
            Verify and continue
        </button>
    </form>
</main>

<script>
    document.getElementById('otp-form').addEventListener('submit', function () {
        const button = document.getElementById('otp-submit');
        button.disabled = true;
        button.textContent = 'Verifying…';
    });
</script>
@endsection
