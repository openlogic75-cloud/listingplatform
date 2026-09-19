<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Sign in - {{ config('branding.name') }}</title>
    <link rel="stylesheet" href="{{ \App\Support\AssetVersion::url('css/admin-tokens.css') }}">
    <link rel="stylesheet" href="{{ \App\Support\AssetVersion::url('css/admin.css') }}">
    @if (is_file(public_path(config('branding.favicon'))))
        <link rel="icon" href="{{ asset(config('branding.favicon')) }}">
    @endif
</head>
<body class="login-page">
    <main class="login-card">
        <div class="brand">
            @include('components.brand-logo', ['height' => 28])
        </div>
        <h1 style="margin-top: var(--adm-space-4);">Sign in</h1>
        <p class="muted small">
            Members only. Buyer accounts are not required on this platform.
        </p>

        @if ($errors->any())
            <p class="alert error" role="alert">{{ $errors->first() }}</p>
        @endif

        <form method="post" action="{{ route('login.attempt') }}">
            @csrf

            <div class="field">
                <label for="email">Email address</label>
                <input id="email" name="email" type="email" required
                       value="{{ old('email') }}" autocomplete="username">
                @error('email')
                    <p class="field-error" role="alert">{{ $message }}</p>
                @enderror
            </div>

            <div class="field">
                <label for="password">Password</label>
                <input id="password" name="password" type="password" required
                       autocomplete="current-password">
                @error('password')
                    <p class="field-error" role="alert">{{ $message }}</p>
                @enderror
            </div>

            <label class="checkbox" for="remember">
                <input id="remember" name="remember" type="checkbox" value="1">
                <span class="small">Keep me signed in on this device</span>
            </label>

            <button class="button" type="submit" style="width: 100%; margin-top: var(--adm-space-4);">
                Sign in
            </button>
        </form>

        <p class="small" style="margin-top: var(--adm-space-5);">
            No account yet? <a href="{{ route('register') }}">Register here</a>.
            <span class="muted">Administrators sign in at the <a href="{{ route('admin.login') }}">admin dashboard</a>.</span>
        </p>
        <p class="small" style="margin: var(--adm-space-1) 0 0;">
            <a href="{{ route('home') }}">Back to site</a>
        </p>
    </main>
</body>
</html>
