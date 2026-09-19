@extends('layouts.app')

@section('title', 'Booking '.$booking->code.' - '.config('branding.name'))

@section('content')
    <section class="container section">
        <div class="donation-box">
            <h2>Booking received</h2>
            <p style="color: var(--color-muted);">
                Your reference code is below. The seller will contact you on the
                phone ending in <strong>{{ $phoneLast4 }}</strong>.
            </p>

            <span class="upi-id">{{ $booking->code }}</span>

            <dl class="listing-facts" style="margin-top: var(--space-3); text-align: left;">
                @foreach ($booking->items as $item)
                    <div>
                        <dt>{{ $item->product?->title ?? 'Item' }}</dt>
                        <dd>Quantity {{ $item->quantity }}@if($item->unit_price_snapshot !== null) &middot; {{ config('app.currency_symbol', 'Rs. ') }}{{ number_format((float) $item->unit_price_snapshot, 2) }} each @endif</dd>
                    </div>
                @endforeach
                <div><dt>Seller</dt><dd>{{ $booking->vendor?->display_name }}</dd></div>
                <div><dt>Status</dt><dd>{{ ucfirst(str_replace('_', ' ', $booking->status)) }}</dd></div>
            </dl>

            <p style="font-size: 0.85rem; color: var(--color-muted);">
                Settlement happens directly with the seller - the platform never
                takes a cut and never touches money. Track this booking in the
                app with the code and your phone number.
            </p>

            <a class="btn btn-primary" href="{{ route('catalog') }}">Keep browsing</a>
        </div>
    </section>
@endsection
