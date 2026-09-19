@extends('layouts.app')

@section('title', 'PG, rentals & homestays - '.config('branding.name'))

@section('content')
    <section class="container section">
        <h2>PG, rentals &amp; homestays</h2>
        <p style="color: var(--color-muted);">
            Rooms, paying-guest accommodation, rentals and homestays. No account
            needed to browse — contact the owner directly to arrange a stay.
        </p>

        <form class="catalog-filters" method="GET" action="{{ route('stays') }}">
            <div class="catalog-filter-field">
                <label for="stay-q">Search stays</label>
                <input id="stay-q" type="search" name="q" value="{{ $filters['q'] ?? '' }}"
                       placeholder="Rooms, rentals and homestays" maxlength="120">
            </div>

            <div class="catalog-filter-field">
                <label for="stay-district">District</label>
                <select id="stay-district" name="district_id">
                    <option value="">All districts</option>
                    @foreach ($districts as $district)
                        <option value="{{ $district->id }}" @selected((string) ($filters['district_id'] ?? '') === (string) $district->id)>
                            {{ $district->name }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div class="catalog-filter-field">
                <label for="stay-locality">Locality</label>
                <select id="stay-locality" name="locality_id">
                    <option value="">All localities</option>
                    @foreach ($districts as $district)
                        @if ($district->localities->isNotEmpty())
                            <optgroup label="{{ $district->name }}">
                                @foreach ($district->localities as $locality)
                                    <option value="{{ $locality->id }}" @selected((string) ($filters['locality_id'] ?? '') === (string) $locality->id)>
                                        {{ $locality->name }}
                                    </option>
                                @endforeach
                            </optgroup>
                        @endif
                    @endforeach
                </select>
            </div>

            <div class="catalog-filter-field">
                <label for="stay-min">Minimum price</label>
                <input id="stay-min" type="number" name="min_price" min="0" step="0.01"
                       value="{{ $filters['min_price'] ?? '' }}" placeholder="No minimum">
            </div>

            <div class="catalog-filter-field">
                <label for="stay-max">Maximum price</label>
                <input id="stay-max" type="number" name="max_price" min="0" step="0.01"
                       value="{{ $filters['max_price'] ?? '' }}" placeholder="No maximum">
            </div>

            <div class="catalog-filter-actions">
                <button class="btn btn-primary" type="submit">Search</button>
                @if (collect($filters)->filter()->isNotEmpty())
                    <a class="btn btn-secondary" href="{{ route('stays') }}">Clear</a>
                @endif
            </div>
        </form>

        @if ($products->isEmpty())
            <div class="empty-state">
                <img src="{{ asset('img/empty.svg') }}" alt="" aria-hidden="true">
                @if (collect($filters)->filter()->isNotEmpty())
                    <h3>Nothing matched those filters</h3>
                    <p>Try a different area or price range.</p>
                @else
                    <h3>No stays listed yet</h3>
                    <p>Owners are onboarding now. Check back soon.</p>
                @endif
                <a class="btn btn-primary" href="{{ route('catalog') }}">Browse all listings</a>
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
