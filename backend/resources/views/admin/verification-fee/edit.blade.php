@extends('layouts.admin')

@section('title', 'Verification fee')
@section('heading', 'Verification fee')
@section('lede', 'Verification is a paid service: the fee pays the volunteer who travelled to the site, not the platform. Amount is display-only - the vendor pays the volunteer directly at the visit; this platform never stores or processes payment credentials.')

@section('content')
    <div class="stack">
        <form method="post" action="{{ route('admin.verification-fee.update') }}">
            @csrf
            @method('PUT')

            <section class="card">
                <header>
                    <div>
                        <h2>Fee amount</h2>
                        <p>Shown on listing and seller pages and in the app before a verification visit is booked.</p>
                    </div>
                </header>

                <div class="field">
                    <label for="amount_inr">Verification fee (INR)</label>
                    <input id="amount_inr" name="amount_inr" type="number" min="0" step="0.01" max="100000"
                           value="{{ old('amount_inr', $setting->amount_inr) }}"
                           placeholder="e.g. 300"
                           aria-describedby="fee-help">
                    <p id="fee-help" class="small muted">
                        Leave empty to hide the fee until the amount is decided.
                        The fee is collected by the volunteer directly; the platform
                        never holds or forwards payment.
                    </p>
                </div>
            </section>

            <button class="button" type="submit">Save verification fee</button>
        </form>

        <section class="card">
            <h2>How payment works</h2>
            <p class="muted">
                The app and website show the fee amount next to the verified badge
                information. Settlement happens directly between the vendor and the
                volunteer at the site — cash or UPI — never through this platform.
                Each issued badge snapshots the fee at the time it was issued, so
                later changes never rewrite history.
            </p>
        </section>
    </div>
@endsection