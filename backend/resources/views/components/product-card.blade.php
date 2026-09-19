<article class="card product-card">
    <a href="{{ route('listing.show', $product) }}" class="product-card-link">
        @if (collect($product->images ?? [])->isNotEmpty())
            <img class="product-card-image" src="{{ Storage::disk('public')->url($product->images[0]) }}" alt="Photo of {{ $product->title }}" loading="lazy" width="320" height="200">
        @else
            <div class="product-card-image product-card-image-empty" role="img" aria-label="No photo for {{ $product->title }}">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" aria-hidden="true"><path d="M3 7l9 6 9-6M4 5h16a1 1 0 0 1 1 1v12a1 1 0 0 1-1 1H4a1 1 0 0 1-1-1V6a1 1 0 0 1 1-1z"/></svg>
            </div>
        @endif

        <h3>{{ $product->title }}</h3>

        <div class="product-card-meta">
            <span class="chip chip-{{ str_replace('_', '-', $product->category) }}">
                {{ ['traditional' => 'Traditional', 'agro' => 'Agro', 'rental_homestay' => 'Rental / Homestay', 'farm_reseller' => 'Farm produce (reseller)'][$product->category] ?? $product->category }}
            </span>
            @if ($product->verified_badge !== null)
                <span class="chip chip-verified" title="Verified by {{ $product->verified_badge->volunteer_name }}">
                    <svg class="icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" aria-hidden="true"><path d="M9 12l2 2 4-4M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0z"/></svg>
                    Verified
                </span>
            @endif
        </div>
    </a>

    @if ($product->price !== null)
        <p class="price">{{ config('app.currency_symbol', 'Rs. ') }}{{ number_format((float) $product->price, 2) }}{{ $product->unit ? ' / '.e($product->unit) : '' }}</p>
    @endif

    @if ($product->moq > 1)
        <p class="moq">Minimum order: {{ $product->moq }}</p>
    @endif
</article>
