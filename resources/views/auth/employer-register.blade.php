<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>Employer Registration — Ascendia</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
<main class="container py-5">
    <form method="post" action="{{ route('register.store') }}" class="card shadow-sm mx-auto p-4" style="max-width:520px">
        @csrf
        <input type="hidden" name="account_type" value="employer">
        <h1 class="h3">Create employer account</h1>
        <p class="text-secondary">Verify your work email, create a company profile and start posting jobs.</p>
        @if ($errors->any())
            <div class="alert alert-danger"><ul class="mb-0">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul></div>
        @endif
        @foreach (['name' => ['Full name','text'], 'email' => ['Work email','email'], 'password' => ['Password','password'], 'password_confirmation' => ['Confirm password','password']] as $name => [$label,$type])
            <div class="mb-3">
                <label class="form-label" for="{{ $name }}">{{ $label }}</label>
                <input class="form-control" id="{{ $name }}" type="{{ $type }}" name="{{ $name }}" value="{{ in_array($name, ['name','email']) ? old($name) : '' }}" required>
            </div>
        @endforeach
        <button class="btn btn-primary w-100" type="submit">Send verification code</button>
        <a class="btn btn-outline-primary w-100 mt-2" href="{{ route('employer.login') }}">Already registered? HR Login</a>
    </form>
</main>
</body>
</html>
