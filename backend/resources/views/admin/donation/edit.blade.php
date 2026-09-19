@extends('layouts.admin')

@section('title', 'Donation settings')
@section('heading', 'Donation settings')
@section('lede', 'The platform charges no commission, so donations cover hosting, volunteer travel and operations. Only the UPI ID and QR image are published - no payment credentials are ever stored here.')

@section('content')
    <div class="stack">
        <form method="post" action="{{ route('admin.donation.update') }}" enctype="multipart/form-data">
            @csrf
            @method('PUT')

            <section class="card">
                <header>
                    <div>
                        <h2>UPI details</h2>
                        <p>Shown on the website donation page and in the app.</p>
                    </div>
                </header>

                <div class="field">
                    <label for="upi_id">UPI ID</label>
                    <input id="upi_id" name="upi_id" type="text" maxlength="100"
                           value="{{ old('upi_id', $setting?->upi_id) }}"
                           placeholder="name@bank" inputmode="email"
                           aria-describedby="upi-help">
                    <p id="upi-help" class="small muted">
                        Format: <code>name@bank</code>. Leave empty to hide donations until
                        the account is ready.
                    </p>
                </div>

                <div class="field">
                    <label for="qr_image">QR image</label>
                    <input id="qr_image" name="qr_image" type="file"
                           accept="image/jpeg,image/png,image/webp"
                           aria-describedby="qr-help">
                    <p id="qr-help" class="small muted">
                        JPEG, PNG or WebP. Re-encoded on upload through the same validator
                        used for listing photos.
                    </p>
                </div>

                @if ($setting?->qr_path)
                    <div>
                        <p class="small muted">Current QR</p>
                        <img class="thumb"
                             src="{{ Storage::disk('public')->url($setting->qr_path) }}"
                             alt="Current donation QR code" width="160" height="160">
                    </div>
                @endif
            </section>

            <button class="button" type="submit">Save donation settings</button>
        </form>

        <section class="card">
            <h2>What is published</h2>
            <p class="muted">
                The app and website display the UPI ID plus a <code>upi://pay</code> link
                that opens the donor's own UPI app. Payment happens entirely inside that
                app: this platform never sees an amount, a UPI PIN or a transaction.
            </p>
        </section>
    </div>
@endsection