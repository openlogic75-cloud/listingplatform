@extends('layouts.app')

@section('title', 'Bookings - '.config('branding.name'))

@section('content')
    <section class="dash">
        <div class="container">
            <header class="dash-head">
                <div>
                    <div class="dash-eyebrow">
                        <span class="dash-role">Vendor</span>
                    </div>
                    <h1>Bookings</h1>
                    <p class="dash-lede">
                        Orders placed with your shop. Move each one along as you
                        fulfil it — settlement happens directly with the buyer.
                    </p>
                </div>
                <div class="dash-actions">
                    <a class="btn btn-secondary" href="{{ route('dashboard') }}">Back to dashboard</a>
                </div>
            </header>

            @if ($bookings->isEmpty())
                <div class="dash-card">
                    <div class="dash-empty">
                        <svg width="34" height="34" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M4 19a2 2 0 1 0 4 0a2 2 0 0 0 -4 0"/><path d="M15 19a2 2 0 1 0 4 0a2 2 0 0 0 -4 0"/><path d="M17 17h-11v-14h-2"/><path d="M6 5l14 1l-1 7h-13"/></svg>
                        <p>No bookings yet. They appear here the moment a buyer books one of your listings.</p>
                        <a class="btn btn-secondary" href="{{ route('dashboard') }}">Manage listings</a>
                    </div>
                </div>
            @else
                <div class="dash-grid">
                    @foreach ($bookings as $booking)
                        <div class="dash-card dash-card--wide">
                            <div class="dash-card-head">
                                <div>
                                    <h2>{{ $booking->code }}</h2>
                                    <p class="muted small" style="margin: 0;">
                                        {{ $booking->contact_name }}
                                        &middot;
                                        <a href="tel:{{ $booking->contact_phone }}">{{ $booking->contact_phone }}</a>
                                        &middot;
                                        {{ $booking->created_at->format('d M Y, H:i') }}
                                    </p>
                                </div>
                                <span class="chip chip-{{ $booking->status }}">{{ \Illuminate\Support\Str::headline($booking->status) }}</span>
                            </div>

                            <ul class="dash-list">
                                @foreach ($booking->items as $item)
                                    <li class="dash-row">
                                        <div class="dash-row-body">
                                            <div class="dash-row-title">{{ $item->product?->title ?? 'Listing removed' }}</div>
                                            <div class="dash-row-meta">
                                                Quantity {{ $item->quantity }}
                                                @if ($item->unit_price_snapshot !== null)
                                                    &middot; {{ config('app.currency_symbol', 'Rs. ') }}{{ number_format((float) $item->unit_price_snapshot, 2) }} each
                                                @endif
                                            </div>
                                        </div>
                                    </li>
                                @endforeach
                            </ul>

                            @if ($booking->notes)
                                <p class="muted small" style="margin-top: var(--space-2);">
                                    Buyer notes: {{ $booking->notes }}
                                </p>
                            @endif

                            @php $next = $transitions[$booking->status] ?? []; @endphp

                            @if ($next !== [])
                                <div class="dash-actions" style="margin-top: var(--space-2);">
                                    @foreach ($next as $status)
                                        <form method="post" action="{{ route('dashboard.bookings.status', $booking) }}">
                                            @csrf
                                            <input type="hidden" name="status" value="{{ $status }}">
                                            <button class="btn {{ $status === 'cancelled' ? 'btn-secondary' : 'btn-primary' }} btn-sm" type="submit">
                                                {{ match ($status) {
                                                    'confirmed' => 'Confirm',
                                                    'cancelled' => 'Cancel',
                                                    'picked_up' => 'Mark picked up',
                                                    'in_transit' => 'Mark in transit',
                                                    'delivered' => 'Mark delivered',
                                                    'completed' => 'Mark completed',
                                                    default => \Illuminate\Support\Str::headline($status),
                                                } }}
                                            </button>
                                        </form>
                                    @endforeach
                                </div>
                            @endif
                        </div>
                    @endforeach
                </div>
            @endif
        </div>
    </section>
@endsection
