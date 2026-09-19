@extends('layouts.app')

@section('title', 'Farm produce for resellers - '.config('branding.name'))

@section('content')
    <section class="container section">
        <h2>Farm produce for resellers</h2>
        <p style="color: var(--color-muted);">
            Bulk farm produce from farmers across the districts. Resellers can
            buy in quantity, and a collector can bring it in from the
            sub-division to a hub district.
        </p>
        @if ($hubs->isNotEmpty())
            <p class="muted small">
                Collectors deliver to: {{ $hubs->pluck('name')->join(', ') }}.
            </p>
        @endif

        <form class="catalog-filters" method="GET" action="{{ route('reseller.produce') }}">
            <label class="visually-hidden" for="rp-q">Search farm produce</label>
            <input id="rp-q" type="search" name="q" value="{{ $filters['q'] ?? '' }}"
                   placeholder="Search farm produce" maxlength="120">

            <label class="visually-hidden" for="rp-district">District</label>
            <select id="rp-district" name="district_id">
                <option value="">All districts</option>
                @foreach ($districts as $district)
                    <option value="{{ $district->id }}" @selected((string) ($filters['district_id'] ?? '') === (string) $district->id)>
                        {{ $district->name }}
                    </option>
                @endforeach
            </select>

            <label class="visually-hidden" for="rp-locality">Locality</label>
            <select id="rp-locality" name="locality_id">
                <option value="">All sub-divisions</option>
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

            <label class="visually-hidden" for="rp-min">Minimum price</label>
            <input id="rp-min" type="number" name="min_price" min="0" step="0.01"
                   value="{{ $filters['min_price'] ?? '' }}" placeholder="Min price">

            <label class="visually-hidden" for="rp-max">Maximum price</label>
            <input id="rp-max" type="number" name="max_price" min="0" step="0.01"
                   value="{{ $filters['max_price'] ?? '' }}" placeholder="Max price">

            <button class="btn btn-primary" type="submit">Search</button>
            @if (collect($filters)->filter()->isNotEmpty())
                <a class="btn btn-secondary" href="{{ route('reseller.produce') }}">Clear</a>
            @endif
        </form>

        @if ($products->isEmpty())
            <div class="empty-state">
                <img src="{{ asset('img/empty.svg') }}" alt="" aria-hidden="true">
                <h3>No reseller farm produce listed yet</h3>
                <p>Farmers are adding bulk listings now. Check back soon.</p>
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
