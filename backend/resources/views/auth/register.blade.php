<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Register - {{ config('branding.name') }}</title>
    <link rel="stylesheet" href="{{ \App\Support\AssetVersion::url('css/admin-tokens.css') }}">
    <link rel="stylesheet" href="{{ \App\Support\AssetVersion::url('css/admin.css') }}">
    @if (is_file(public_path(config('branding.favicon'))))
        <link rel="icon" href="{{ asset(config('branding.favicon')) }}">
    @endif
</head>
<body class="login-page">
    <main class="login-card wide">
        <div class="brand">
            @include('components.brand-logo', ['height' => 28])
        </div>
        <h1 style="margin-top: var(--adm-space-4);">Create an account</h1>
        <p class="muted small">
            Accounts are for the people who run the market — vendors, drivers,
            collectors, skilled workers and volunteers. Buyers browse as guests.
        </p>

        @if ($errors->any())
            <p class="alert error" role="alert">{{ $errors->first() }}</p>
        @endif

        <form method="post" action="{{ route('register.attempt') }}">
            @csrf

            <fieldset class="role-radios">
                <legend>I want to join as</legend>
                @foreach ([
                    'vendor' => ['label' => 'Vendor', 'detail' => 'Sell goods or rent'],
                    'driver' => ['label' => 'Driver', 'detail' => 'Do deliveries'],
                    'collector' => ['label' => 'Collector', 'detail' => 'Pick up goods'],
                    'skilled_worker' => ['label' => 'Skilled worker', 'detail' => 'Offer skilled help'],
                    'volunteer' => ['label' => 'Volunteer', 'detail' => 'Verify listings'],
                ] as $value => $meta)
                    <label class="radio" for="role-{{ $value }}">
                        <input id="role-{{ $value }}" type="radio" name="role" value="{{ $value }}"
                               {{ old('role', 'vendor') === $value ? 'checked' : '' }}>
                        <span class="small">
                            <strong>{{ $meta['label'] }}</strong>
                            <span class="muted">— {{ $meta['detail'] }}</span>
                        </span>
                    </label>
                @endforeach
                @error('role')
                    <p class="field-error" role="alert">{{ $message }}</p>
                @enderror
            </fieldset>

            <div class="vendor-fields">
                <p class="form-section">Your shop</p>

                <div class="field">
                    <label for="display_name">Shop name</label>
                    <input id="display_name" name="display_name" type="text"
                           maxlength="120" value="{{ old('display_name') }}">
                    @error('display_name')
                        <p class="field-error" role="alert">{{ $message }}</p>
                    @enderror
                </div>

                <div class="field">
                    <label for="vendor_category">Category</label>
                    <select id="vendor_category" name="vendor_category">
                        <option value="traditional" @selected(old('vendor_category', 'traditional') === 'traditional')>Traditional products</option>
                        <option value="agro" @selected(old('vendor_category') === 'agro')>Agro products</option>
                        <option value="rental_homestay" @selected(old('vendor_category') === 'rental_homestay')>Rental / Homestay</option>
                    </select>
                    @error('vendor_category')
                        <p class="field-error" role="alert">{{ $message }}</p>
                    @enderror
                </div>
            </div>

            <p class="form-section">Your details</p>

            <div class="field">
                <label for="name">Full name</label>
                <input id="name" name="name" type="text" required maxlength="120"
                       value="{{ old('name') }}" autocomplete="name">
                @error('name')
                    <p class="field-error" role="alert">{{ $message }}</p>
                @enderror
            </div>

            <div class="field">
                <label for="email">Email address</label>
                <input id="email" name="email" type="email" required maxlength="180"
                       value="{{ old('email') }}" autocomplete="email">
                @error('email')
                    <p class="field-error" role="alert">{{ $message }}</p>
                @enderror
            </div>

            <div class="field">
                <label for="phone">Phone <span class="muted small">(optional — used for pickup coordination)</span></label>
                <input id="phone" name="phone" type="tel" maxlength="20"
                       value="{{ old('phone') }}" autocomplete="tel">
                @error('phone')
                    <p class="field-error" role="alert">{{ $message }}</p>
                @enderror
            </div>

            <div class="field">
                <label for="district_id">District <span class="muted small">(optional)</span></label>
                <select id="district_id" name="district_id">
                    <option value="">Not listed yet</option>
                    @foreach ($districts as $district)
                        <option value="{{ $district->id }}" @selected(old('district_id') == $district->id)>{{ $district->name }}</option>
                    @endforeach
                </select>
                @error('district_id')
                    <p class="field-error" role="alert">{{ $message }}</p>
                @enderror
            </div>

            <p class="form-section">Password</p>

            <div class="field">
                <label for="password">Password</label>
                <input id="password" name="password" type="password" required
                       minlength="8" autocomplete="new-password">
                @error('password')
                    <p class="field-error" role="alert">{{ $message }}</p>
                @enderror
            </div>

            <div class="field">
                <label for="password_confirmation">Confirm password</label>
                <input id="password_confirmation" name="password_confirmation"
                       type="password" required autocomplete="new-password">
            </div>

            <label class="checkbox" for="accept-terms" style="align-items: flex-start;">
                <input id="accept-terms" type="checkbox" name="accept_terms" value="1"
                       required @checked(old('accept_terms'))>
                <span class="small">
                    I am {{ config('legal.minimum_age') }} or older and accept the
                    <a href="{{ route('terms') }}" target="_blank" rel="noopener">Terms</a>
                    and the
                    <a href="{{ route('privacy') }}" target="_blank" rel="noopener">Privacy Policy</a>.
                </span>
            </label>
            @error('accept_terms')
                <p class="field-error" role="alert">{{ $message }}</p>
            @enderror

            <button class="button" type="submit" style="width: 100%; margin-top: var(--adm-space-2);">
                Create account
            </button>
        </form>

        <p class="small" style="margin-top: var(--adm-space-5);">
            Already registered? <a href="{{ route('login') }}">Sign in here</a>.
        </p>
        <p class="small" style="margin: var(--adm-space-1) 0 0;">
            <a href="{{ route('home') }}">Back to site</a>
        </p>
    </main>
</body>
</html>
