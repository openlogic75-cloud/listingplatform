@extends('layouts.admin')

@section('title', 'Account security')
@section('heading', 'Account security')
@section('lede', 'Change the admin password. A one-time code is sent to the admin email before the new password is saved.')

@section('content')
    <div class="stack">
        <section class="card">
            <header>
                <div>
                    <h2>Change password</h2>
                    <p>The code expires after 10 minutes and allows five attempts.</p>
                </div>
            </header>

            <form method="post" action="{{ route('admin.password.request') }}">
                @csrf
                <div class="field">
                    <label for="new-password">New password</label>
                    <input id="new-password" name="new_password" type="password" required minlength="8" autocomplete="new-password">
                </div>

                <div class="field">
                    <label for="new-password-confirmation">Confirm new password</label>
                    <input id="new-password-confirmation" name="new_password_confirmation" type="password" required minlength="8" autocomplete="new-password">
                </div>

                <button class="button" type="submit">Send confirmation code</button>
            </form>
        </section>

        @if (session('status'))
            <section class="card">
                <h2>Enter the email code</h2>
                <p class="muted">Check the admin email account and enter the six-digit code.</p>
                <form method="post" action="{{ route('admin.password.confirm') }}">
                    @csrf
                    <div class="field">
                        <label for="password-otp">Confirmation code</label>
                        <input id="password-otp" name="otp" type="text" inputmode="numeric" autocomplete="one-time-code" pattern="[0-9]{6}" maxlength="6" required>
                    </div>
                    <button class="button" type="submit">Change password</button>
                </form>
            </section>
        @endif
    </div>
@endsection
