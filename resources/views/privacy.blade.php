@extends('layouts.site')
@section('title', 'Privacy notice')
@section('content')
<section class="section"><div class="container" style="max-width:820px">
    <div class="panel">
        <h1 style="margin-top:0">Privacy notice</h1>
        <p>This notice explains what {{ config('app.name') }} records when you use the site, as required by the Digital Personal Data Protection Act, 2023.</p>
        <h2>What we record</h2>
        <ul>
            <li><b>Pages you visit</b> and the time of each visit, with your browser and device type.</li>
            <li><b>If you are signed in</b>, these visits are linked to your account (name and email) so we can show you relevant jobs, companies and recommendations, and so our team can understand how members use the site.</li>
            <li><b>If you are not signed in</b>, we do not know who you are. We keep an anonymous cookie, an approximate state or country derived from your IP address, and a one-way hash of the IP address — never the address itself.</li>
            <li>Profiles, applications, financial plans and other details you choose to enter.</li>
        </ul>
        <h2>Why</h2>
        <p>To run and improve the site, personalise recommendations, prevent abuse and measure which pages are useful. We do not sell personal data.</p>
        <h2>Your choices</h2>
        <ul>
            <li>Browse without signing in to stay anonymous.</li>
            <li>Ask us to access, correct or delete the data linked to your account, or withdraw consent, by writing to <a href="mailto:{{ config('platform.contact.email', 'sachinsoni77998@gmail.com') }}">{{ config('platform.contact.email', 'sachinsoni77998@gmail.com') }}</a>.</li>
        </ul>
        <p style="color:var(--muted);font-size:13px">Visit records are kept for up to 24 months.</p>
    </div>
</div></section>
@endsection
