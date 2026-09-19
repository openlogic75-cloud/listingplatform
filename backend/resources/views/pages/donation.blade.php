@extends('layouts.app')

@section('title', "Donate - ".config('branding.name'))

@section('metaDescription', 'Support the platform via UPI. Donations keep the marketplace free and commission-free.')

@section('content')
    <section class="container section">
        <div class="donation-box">
            <h2>Support the platform</h2>
            <p style="color: var(--color-muted);">
                {{ config('branding.name') }} is free for buyers, sellers and drivers.
                Donations cover volunteer travel for verifications, hosting, and
                day-to-day operations. The platform never charges commission.
            </p>

            @if ($donation && $donation->upi_id)
                @if ($donation->qr_path)
                    <img src="{{ Storage::disk('public')->url($donation->qr_path) }}" alt="UPI QR code for donation" width="240" height="240">
                @endif
                <p>Pay directly from any UPI app to:</p>
                <span class="upi-id">{{ $donation->upi_id }}</span>
                <p style="font-size: 0.85rem; color: var(--color-muted); margin-top: var(--space-3);">
                    Donations go straight to the platform's UPI account. No payment
                    details are stored on this website.
                </p>
            @else
                <div class="empty-state">
                    <img src="{{ asset('img/empty.svg') }}" alt="" aria-hidden="true">
                    <h3>Donation details are being set up</h3>
                    <p>The admin team has not published the UPI details yet. Please check back soon.</p>
                </div>
            @endif
        </div>
    </section>
@endsection
