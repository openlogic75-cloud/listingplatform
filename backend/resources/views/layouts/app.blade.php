<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="description" content="{{ $metaDescription ?? config('branding.name').' connects buyers, sellers, logistics and verification volunteers. Runs on donations, charges no commission.' }}">
    <title>@yield('title', config('branding.name'))</title>
    <link rel="stylesheet" href="{{ \App\Support\AssetVersion::url('css/tokens.css') }}">
    <link rel="stylesheet" href="{{ \App\Support\AssetVersion::url('css/app.css') }}">
    @if (is_file(public_path(config('branding.favicon'))))
        <link rel="icon" href="{{ asset(config('branding.favicon')) }}">
    @endif
</head>
<body>
    <nav class="site-nav" aria-label="Main navigation">
        <div class="container">
            <a class="brand" href="{{ route('home') }}">@include('components.brand-logo')</a>
            <ul>
                <li><a href="{{ route('catalog') }}">Catalog</a></li>
                <li><a href="{{ route('stays') }}">PG &amp; stays</a></li>
                <li><a href="{{ route('workers') }}">Skilled workers</a></li>
                <li><a href="{{ route('transport') }}">Transport &amp; errands</a></li>
                <li><a href="{{ route('blog') }}">Blog</a></li>
                <li><a href="{{ route('about') }}">About</a></li>
                <li><a href="{{ route('donation') }}">Donate</a></li>
                @auth
                    @if (auth()->user()->role === 'admin')
                        <li><a href="{{ route('admin.donation.edit') }}">Admin</a></li>
                    @else
                        <li><a href="{{ route('dashboard') }}">Dashboard</a></li>
                    @endif
                    <li>
                        <form method="post" action="{{ route('logout') }}" class="nav-form">
                            @csrf
                            <button type="submit" class="nav-link">Sign out</button>
                        </form>
                    </li>
                @else
                    <li><a href="{{ route('login') }}">Sign in</a></li>
                    <li><a href="{{ route('register') }}">Register</a></li>
                @endauth
            </ul>
        </div>
    </nav>

    <main>
        @if (session('status'))
            <div class="container" style="padding-top: var(--space-3);">
                <p class="alert success" role="status">{{ session('status') }}</p>
            </div>
        @endif

        @yield('content')
    </main>

    <footer class="site-footer">
        <div class="container">
            <span>Runs on donations. No commission. No transactions on-platform.</span>
            <span>
                <a href="{{ route('donation') }}">Support the platform</a>
                &middot;
                <a href="{{ route('about') }}">Mission</a>
            </span>
            <span>
                <a href="{{ route('terms') }}">Terms</a>
                &middot;
                <a href="{{ route('privacy') }}">Privacy</a>
                &middot;
                <a href="{{ route('disclaimer') }}">Disclaimer</a>
            </span>
        </div>
    </footer>
</body>
</html>
