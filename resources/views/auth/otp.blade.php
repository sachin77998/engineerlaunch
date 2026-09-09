@extends('layouts.app')

@section('title', 'Verify Email — Ascendia')

@section('content')
<style>
    .sitebar .sitebar-inner { width:100%; margin:0; padding-left:12px; padding-right:24px; }
    .sitebar .site-nav { flex:1; }
    .sitebar .site-nav>.btn:first-of-type { margin-left:auto; }
    .otp-page { min-height: 72vh; display: grid; place-items: center; padding: 72px 20px; background: radial-gradient(circle at 18% 20%,rgba(37,99,235,.12),transparent 30%),linear-gradient(145deg,#edf4ff,#f8fafc 58%,#eef2ff); }
    .otp-card { position:relative; overflow:hidden; width: 100%; max-width: 570px; padding: 42px; background: rgba(255,255,255,.96); border: 1px solid #cbdaf1; border-radius: 24px; box-shadow: 0 28px 70px rgba(24,56,105,.16); }
    .otp-card::before { content:""; position:absolute; inset:0 0 auto; height:6px; background:linear-gradient(90deg,#1d4ed8,#4f8cf7,#7c3aed); }
    .otp-icon { width:56px; height:56px; display:grid; place-items:center; margin-bottom:22px; border-radius:16px; background:linear-gradient(135deg,#e0ecff,#eef2ff); color:#245bc6; font-size:25px; box-shadow:inset 0 0 0 1px #cbdcf8; }
    .otp-card h1 { margin: 0 0 12px; color: #173b6c; font-family:Georgia,'Times New Roman',serif; font-size: 34px; line-height:1.2; letter-spacing:-.4px; }
    .otp-card p { margin:0 0 24px; color: #60708a; line-height: 1.7; }
    .otp-field-label { display:block; margin:0 0 9px; color:#233d63; font-size:14px; font-weight:750; }
    .otp-input { width: 100%; height:64px; padding: 14px 18px; border: 1px solid #afc3df; border-radius: 12px; background:#fbfdff; color:#17345f; font-size: 25px; font-weight:700; letter-spacing: 10px; text-align: center; }
    .otp-input:focus { border-color:#326fe0!important; box-shadow:0 0 0 4px rgba(50,111,224,.14)!important; }
    .otp-alert { margin: 18px 0; padding: 12px 14px; border: 1px solid #fde68a; border-radius: 8px; background: #fffbeb; color: #92400e; }
    .otp-code { display: block; margin-top: 5px; font-size: 22px; font-weight: 800; letter-spacing: 4px; }
    .otp-error { margin: 12px 0; color: #b42318; }
    .otp-submit { width:100%; min-height:52px; display:flex; align-items:center; justify-content:center; margin-top:18px; padding:13px 20px; border:0!important; border-radius:12px!important; background:linear-gradient(135deg,#214fae,#3a7bec)!important; color:#fff; font-size:16px; font-weight:800; line-height:1.2; box-shadow:0 12px 26px rgba(37,99,235,.24)!important; cursor:pointer; }
    .otp-submit:hover { transform:translateY(-1px); }
    .otp-submit:disabled { opacity:.72; cursor:wait; transform:none; }
    @media(max-width:600px){.otp-page{padding:40px 16px}.otp-card{padding:32px 22px}.otp-card h1{font-size:29px}}
</style>

<main class="otp-page">
    <form id="otp-form" class="otp-card" method="POST" action="{{ route('otp.verify') }}">
        @csrf
        <div class="otp-icon" aria-hidden="true">✉</div>
        <h1>Please Verify Your Email Address</h1>
        <p>Enter the six digit verification code sent on email address.</p>

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
        @error('rate_limit')
            <div class="otp-error" role="alert">{{ $message }}</div>
        @enderror

        <label for="code" class="otp-field-label">Verification Code</label>
        <input id="code" class="otp-input" name="code" inputmode="numeric"
               autocomplete="one-time-code" pattern="[0-9]{6}" maxlength="6"
               value="{{ old('code', $devOtp ?? '') }}" required autofocus>

        <button id="otp-submit" type="submit" class="btn btn-primary otp-submit">
            Verify and Continue
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
