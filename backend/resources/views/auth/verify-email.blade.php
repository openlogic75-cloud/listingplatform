@extends('layouts.app')

@section('title', 'Verify your email - '.config('branding.name'))

@section('content')
    <section class="container section narrow">
        <div class="auth-card">
            <h1>Verify your email</h1>
            <p>
                We sent a verification link to your email address. Click it
                before signing in or using your account.
            </p>
            <form method="post" action="{{ route('verification.send') }}">
                @csrf
                <button class="btn btn-primary" type="submit">Send the link again</button>
            </form>
            <form method="post" action="{{ route('logout') }}" style="margin-top: var(--space-2);">
                @csrf
                <button class="btn btn-secondary" type="submit">Sign out</button>
            </form>
        </div>
    </section>
@endsection
