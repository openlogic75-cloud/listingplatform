@extends('layouts.app')

@section('title', 'Contact - '.config('branding.name'))
@section('metaDescription', 'Contact Shekuthi and reach the grievance redressal officer.')

@php
    $contactEmail = config('legal.contact_email');
    $grievanceOfficer = config('legal.grievance_officer_name');
    $grievanceEmail = config('legal.grievance_officer_email');
@endphp

@section('content')
    <section class="container section" style="max-width: 820px;">
        <h1>Contact Shekuthi</h1>
        <p>
            Shekuthi is a peer-to-peer connector. Contact this address for
            platform questions, privacy matters, reports or support about the
            service. For a listing, sale, booking, delivery or collection, the
            users involved should first contact each other directly.
        </p>

        <h2>General contact</h2>
        <p>
            <a href="mailto:{{ $contactEmail }}">{{ $contactEmail }}</a>
        </p>

        <h2>Grievance redressal</h2>
        <p>
            Grievance officer: <strong>{{ $grievanceOfficer }}</strong><br>
            Email: <a href="mailto:{{ $grievanceEmail }}">{{ $grievanceEmail }}</a>
        </p>
        <p class="muted small">
            Include enough information for us to understand the issue, but do
            not email passwords, payment credentials or unnecessary personal
            data.
        </p>

        <h2>Peer-to-peer arrangements</h2>
        <p>
            Shekuthi does not set or collect prices, fees or payments. Users
            agree arrangements directly and remain responsible for complying
            with applicable district, state, local-jurisdiction and municipal
            requirements.
        </p>
    </section>
@endsection
