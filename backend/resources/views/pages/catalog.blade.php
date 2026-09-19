@extends('layouts.app')

@section('title', "Catalog - ".config('branding.name'))

@section('content')
    <section class="container section">
        <h2>Catalog</h2>
        <p style="color: var(--color-muted);">
            Traditional products, agro produce, rentals and homestays. Sellers
            list from the mobile app; browsing here needs no account.
        </p>

        <form class="catalog-filters catalog-filters--compact" method="GET" action="{{ route('catalog') }}">
            <div class="catalog-filter-field">
                <label for="filter-q">Search listings</label>
                <input id="filter-q" type="search" name="q" value="{{ $filters['q'] ?? '' }}" placeholder="Titles and descriptions" maxlength="120">
            </div>
            <div class="catalog-filter-field">
                <label for="filter-category">Category</label>
                <select id="filter-category" name="category">
                    <option value="">All categories</option>
                    <option value="traditional" @selected(($filters['category'] ?? '') === 'traditional')>Traditional</option>
                    <option value="agro" @selected(($filters['category'] ?? '') === 'agro')>Agro</option>
                    <option value="rental_homestay" @selected(($filters['category'] ?? '') === 'rental_homestay')>Rental / Homestay</option>
                </select>
            </div>
            <div class="catalog-filter-actions">
                <button class="btn btn-primary" type="submit">Search</button>
                @if (($filters['q'] ?? '') !== '' || ($filters['category'] ?? '') !== '')
                    <a class="btn btn-secondary" href="{{ route('catalog') }}">Clear</a>
                @endif
            </div>
        </form>

        @if ($products->isEmpty())
            <div class="empty-state">
                <img src="{{ asset('img/empty.svg') }}" alt="" aria-hidden="true">
                @if (isset($filters['q']) || isset($filters['category']))
                    <h3>Nothing matched those filters</h3>
                    <p>Try a different search term or category.</p>
                @else
                    <h3>No listings yet</h3>
                    <p>Sellers are onboarding now. Check back soon, or support the platform with a donation.</p>
                @endif
                <a class="btn btn-primary" href="{{ route('donation') }}">Donate</a>
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
