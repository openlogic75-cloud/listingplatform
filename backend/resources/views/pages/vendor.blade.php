@extends('layouts.app')

@section('title', $vendor->display_name.' - '.config('branding.name'))

@section('content')
    <section class="container section">
        <p class="breadcrumb"><a href="{{ route('catalog') }}">Catalog</a> / Sellers</p>

        <h2>{{ $vendor->display_name }}</h2>

        <p class="chip chip-{{ str_replace('_', '-', $vendor->category) }}">
            {{ ['traditional' => 'Traditional products', 'agro' => 'Agro products', 'rental_homestay' => 'Rental / Homestay'][$vendor->category] ?? $vendor->category }}
        </p>

        @if ($vendor->verified_badge !== null)
            <p class="verified-line">
                <svg class="icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" aria-hidden="true"><path d="M9 12l2 2 4-4M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0z"/></svg>
                Verified by {{ $vendor->verified_badge->volunteer_name }}
            </p>
        @elseif ($verificationFee !== null)
            <p class="verified-line">
                <svg class="icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" aria-hidden="true"><path d="M9 12l2 2 4-4M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0z"/></svg>
                Verification available — fee of {{ config('app.currency_symbol', 'Rs. ') }}{{ number_format((float) $verificationFee, 2) }} paid directly to the visiting volunteer
            </p>
        @endif

        @if ($vendor->description)
            <p style="color: var(--color-muted); max-width: 70ch;">{{ $vendor->description }}</p>
        @endif

        <h3 class="vendor-listings-title">Listings</h3>

        @if ($products->isEmpty())
            <div class="empty-state">
                <img src="{{ asset('img/empty.svg') }}" alt="" aria-hidden="true">
                <h3>No active listings right now</h3>
                <p>This seller has nothing published at the moment.</p>
            </div>
        @else
            <div class="product-grid">
                @foreach ($products as $product)
                    @include('components.product-card', ['product' => $product])
                @endforeach
            </div>
        @endif
    </section>
@endsection
