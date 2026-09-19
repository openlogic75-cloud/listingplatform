{{--
    Brand mark (M0.7).

    Renders the logo file from public/ when it exists, otherwise the plain
    wordmark. Swapping the logo means dropping a file in public/img/ - this
    component and every view that uses it stay untouched.
--}}
@php
    $brandName = config('branding.name');
    $brandAlt = config('branding.logo_alt');
    $candidates = array_filter([
        public_path(config('branding.logo')),
        public_path(config('branding.logo_png')),
    ]);
    $logoPath = null;
    foreach ($candidates as $candidate) {
        if (is_file($candidate)) {
            $logoPath = $candidate;
            break;
        }
    }
@endphp
@if ($logoPath !== null)
    <img
        class="brand-mark"
        src="{{ asset(str_replace(public_path().DIRECTORY_SEPARATOR, '', $logoPath)) }}"
        alt="{{ $brandAlt }}"
        height="{{ $height ?? 28 }}"
    >
@else
    <span class="brand-wordmark">{{ $brandName }}</span>
@endif