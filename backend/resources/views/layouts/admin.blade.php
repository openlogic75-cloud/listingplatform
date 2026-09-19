<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex, nofollow">
    <title>@yield('title', 'Dashboard') - {{ config('branding.name') }}</title>
    <link rel="stylesheet" href="{{ \App\Support\AssetVersion::url('css/admin-tokens.css') }}">
    <link rel="stylesheet" href="{{ \App\Support\AssetVersion::url('css/admin.css') }}">
    @if (is_file(public_path(config('branding.favicon'))))
        <link rel="icon" href="{{ asset(config('branding.favicon')) }}">
    @endif
</head>
<body>
<div class="shell">
    <aside class="sidebar">
        <a class="brand" href="{{ route('admin.donation.edit') }}">
            @include('components.brand-logo', ['height' => 26])
        </a>

        <nav aria-label="Dashboard sections">
            <ul>
                <li>
                    <a href="{{ route('admin.password.edit') }}"
                       @if (request()->routeIs('admin.password.*')) aria-current="page" @endif>
                        Account security
                    </a>
                </li>
                <li>
                    <a href="{{ route('admin.donation.edit') }}"
                       @if (request()->routeIs('admin.donation.*')) aria-current="page" @endif>
                        Donation settings
                    </a>
                </li>
                <li>
                    <a href="{{ route('admin.verification-fee.edit') }}"
                       @if (request()->routeIs('admin.verification-fee.*')) aria-current="page" @endif>
                        Verification fee
                    </a>
                </li>
                <li>
                    <a href="{{ route('admin.localities.index') }}"
                       @if (request()->routeIs('admin.localities.*', 'admin.districts.*')) aria-current="page" @endif>
                        Districts and localities
                    </a>
                </li>
                <li>
                    <a href="{{ route('admin.verifications.index') }}"
                       @if (request()->routeIs('admin.verifications.*')) aria-current="page" @endif>
                        Verifications
                    </a>
                </li>
                <li>
                    <a href="{{ route('admin.volunteers.index') }}"
                       @if (request()->routeIs('admin.volunteers.*')) aria-current="page" @endif>
                        Volunteers
                    </a>
                </li>
                <li>
                    <a href="{{ route('admin.skills.index') }}"
                       @if (request()->routeIs('admin.skills.*')) aria-current="page" @endif>
                        Skill categories
                    </a>
                </li>
                <li>
                    <a href="{{ route('admin.transport.index') }}"
                       @if (request()->routeIs('admin.transport.*')) aria-current="page" @endif>
                        Transport categories
                    </a>
                </li>
                <li>
                    <a href="{{ route('admin.collectors.index') }}"
                       @if (request()->routeIs('admin.collectors.*')) aria-current="page" @endif>
                        Collectors
                    </a>
                </li>
                <li>
                    <a href="{{ route('admin.posts.index') }}"
                       @if (request()->routeIs('admin.posts.*')) aria-current="page" @endif>
                        Blog
                    </a>
                </li>
                <li>
                    <a href="{{ route('admin.data-requests.index') }}"
                       @if (request()->routeIs('admin.data-requests.*')) aria-current="page" @endif>
                        Data requests
                    </a>
                </li>
            </ul>
        </nav>

        <div class="session">
            <p class="small" style="margin: 0 0 var(--adm-space-2);">
                Signed in as
                <strong>{{ auth()->user()?->name }}</strong>
            </p>
            <form method="post" action="{{ route('admin.logout') }}">
                @csrf
                <button class="button secondary" type="submit">Sign out</button>
            </form>
        </div>
    </aside>

    <main class="main">
        <div class="page-head">
            <div>
                <h1>@yield('heading', 'Dashboard')</h1>
                @hasSection('lede')
                    <p>@yield('lede')</p>
                @endif
            </div>
        </div>

        @if (session('status'))
            <p class="alert" role="status">{{ session('status') }}</p>
        @endif

        @if ($errors->any())
            <div class="alert error" role="alert">
                <strong>Please fix the following:</strong>
                <ul style="margin: var(--adm-space-2) 0 0; padding-left: var(--adm-space-5);">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        @yield('content')
    </main>
</div>
</body>
</html>
