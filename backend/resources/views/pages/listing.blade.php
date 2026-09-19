@extends('layouts.app')

@section('title', $product->title.' - '.config('branding.name'))

@section('content')
    <section class="container section">
        <p class="breadcrumb"><a href="{{ route('catalog') }}">Catalog</a> / {{ ['traditional' => 'Traditional', 'agro' => 'Agro', 'rental_homestay' => 'Rental / Homestay'][$product->category] ?? $product->category }}</p>

        <div class="listing-detail">
            <div class="listing-media">
                @if (collect($product->images ?? [])->isNotEmpty())
                    <img src="{{ Storage::disk('public')->url($product->images[0]) }}" alt="Photo of {{ $product->title }}" width="640" height="480">
                @else
                    <div class="product-card-image-empty listing-media-empty">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" aria-hidden="true"><path d="M3 7l9 6 9-6M4 5h16a1 1 0 0 1 1 1v12a1 1 0 0 1-1 1H4a1 1 0 0 1-1-1V6a1 1 0 0 1 1-1z"/></svg>
                        <span>No photo provided</span>
                    </div>
                @endif
            </div>

            <div class="listing-info">
                <h2>{{ $product->title }}</h2>

                @if ($product->verified_badge !== null)
                    <p class="verified-line">
                        <svg class="icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" aria-hidden="true"><path d="M9 12l2 2 4-4M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0z"/></svg>
                        Verified by
                        @if ($product->verified_badge->post !== null)
                            <a href="{{ route('blog.show', $product->verified_badge->post) }}">{{ $product->verified_badge->volunteer_name }}</a>
                        @else
                            {{ $product->verified_badge->volunteer_name }}
                        @endif
                    </p>
                @elseif ($verificationFee !== null)
                    <p class="verified-line">
                        <svg class="icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" aria-hidden="true"><path d="M9 12l2 2 4-4M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0z"/></svg>
                        Verification available — fee of {{ config('app.currency_symbol', 'Rs. ') }}{{ number_format((float) $verificationFee, 2) }} paid directly to the visiting volunteer
                    </p>
                @endif

                @if ($product->verified_badge !== null || $verificationFee !== null)
                    <p class="muted small" style="margin: 4px 0 0;">
                        A volunteer's on-site visit record — context to weigh up, not a platform guarantee.
                    </p>
                @endif

                @if ($product->price !== null)
                    <p class="price listing-price">{{ config('app.currency_symbol', 'Rs. ') }}{{ number_format((float) $product->price, 2) }}{{ $product->unit ? ' / '.e($product->unit) : '' }}</p>
                @endif

                <dl class="listing-facts">
                    @if ($product->moq > 1)
                        <div><dt>Minimum order</dt><dd>{{ $product->moq }}</dd></div>
                    @endif
                    @if ($product->stock !== null)
                        <div><dt>In stock</dt><dd>{{ number_format($product->stock) }}</dd></div>
                    @endif
                    @if ($product->batch_code)
                        <div><dt>Batch</dt><dd>{{ $product->batch_code }}</dd></div>
                    @endif
                    @if ($product->available_from && $product->available_to)
                        <div><dt>Available</dt><dd>{{ $product->available_from->format('j M Y') }} to {{ $product->available_to->format('j M Y') }}</dd></div>
                    @endif
                    @if ($product->vendor)
                        <div><dt>Sold by</dt><dd><a href="{{ route('vendor.show', $product->vendor) }}">{{ $product->vendor->display_name }}</a></dd></div>
                    @endif
                </dl>

                @if ($product->description)
                    <p class="listing-description">{{ $product->description }}</p>
                @endif

                <div class="listing-actions">
                    <p class="share-row">
                        <button class="btn btn-secondary" type="button" data-share-url="{{ $shareUrl }}">
                            Share this listing
                        </button>
                        <span class="share-note" data-share-note hidden>Link copied</span>
                    </p>

                    @if ($product->status === 'active')
                        <form method="POST" action="{{ route('booking.store', $product) }}"
                              class="booking-form"
                              @if ($product->price !== null) data-unit-price="{{ $product->price }}" @endif>
                            @csrf

                            <div class="booking-head">
                                <h3>Book this item</h3>
                                <span class="booking-badge">No account needed</span>
                            </div>
                            <p class="booking-form-note">The seller contacts you on your phone; settlement is direct.</p>

                            <div class="booking-grid">
                                <div class="booking-field">
                                    <label for="booking-name">Your name</label>
                                    <input id="booking-name" name="contact_name" type="text"
                                           maxlength="120" required value="{{ old('contact_name') }}"
                                           autocomplete="name" placeholder="e.g. Meera Devi">
                                </div>

                                <div class="booking-field">
                                    <label for="booking-phone">Phone</label>
                                    <input id="booking-phone" name="contact_phone" type="tel"
                                           maxlength="20" required value="{{ old('contact_phone') }}"
                                           autocomplete="tel" placeholder="Mobile number">
                                </div>
                            </div>
                            @error('contact_name')<p class="booking-error" role="alert">{{ $message }}</p>@enderror
                            @error('contact_phone')<p class="booking-error" role="alert">{{ $message }}</p>@enderror

                            <div class="booking-field">
                                <label for="booking-qty">Quantity @if ($product->moq > 1)(minimum {{ $product->moq }})@endif</label>
                                <div class="qty-stepper">
                                    <button type="button" class="qty-btn" data-qty-step="-1" aria-label="Decrease quantity">&minus;</button>
                                    <input id="booking-qty" name="quantity" type="number" inputmode="numeric"
                                           min="{{ max(1, $product->moq) }}"
                                           @if ($product->stock !== null) max="{{ $product->stock }}" @endif
                                           value="{{ old('quantity', max(1, $product->moq)) }}" required>
                                    <button type="button" class="qty-btn" data-qty-step="1" aria-label="Increase quantity">+</button>
                                </div>
                                @error('items.0.quantity')<p class="booking-error" role="alert">{{ $message }}</p>@enderror
                                @error('quantity')<p class="booking-error" role="alert">{{ $message }}</p>@enderror
                            </div>

                            @if ($product->price !== null)
                                <div class="booking-total">
                                    <span class="muted small">Estimated total</span>
                                    <span class="booking-total-value" data-total>
                                        {{ config('app.currency_symbol', 'Rs. ') }}{{ number_format((float) $product->price * max(1, $product->moq), 2) }}
                                    </span>
                                </div>
                            @endif

                            <div class="booking-field">
                                <label for="booking-notes">Notes for the seller (optional)</label>
                                <textarea id="booking-notes" name="notes" rows="2" maxlength="1000"
                                          placeholder="Pickup time, packing, anything else">{{ old('notes') }}</textarea>
                            </div>

                            <button class="btn btn-primary booking-submit" type="submit">Place booking</button>
                            <p class="booking-privacy">Your name and phone are used only for this booking and are stored encrypted. The estimate is settled directly with the seller — the platform never handles payment.</p>
                        </form>
                    @else
                        <span class="cta-note">This listing is not open for booking right now.</span>
                    @endif
                </div>
            </div>
        </div>
    </section>

    <script src="{{ \App\Support\AssetVersion::url('js/share.js') }}" defer></script>
    <script src="{{ \App\Support\AssetVersion::url('js/booking-form.js') }}" defer></script>
@endsection
