@extends('layouts.app')

@section('title', 'Disclaimer - '.config('branding.name'))
@section('metaDescription', 'This platform connects people; it does not sell, does not vouch, and does not guarantee any deal, delivery or verification.')

@php
    $entity = config('legal.entity_name');
    $effective = config('legal.effective_date');
    $contact = config('legal.contact_email');
    $ph = fn (?string $v, string $label): string => $v !== null && $v !== '' ? $v : '['.$label.' not yet configured]';
@endphp

@section('content')
    <section class="container section" style="max-width: 820px;">
        <h1>Disclaimer</h1>
        <p class="muted small">Effective {{ $effective }}</p>

        <h2>A connector, not a party</h2>
        <p>
            {{ $entity }} connects sellers, buyers, resellers, logistics
            providers, skilled workers and volunteers. It is not a seller, a
            buyer, a broker, an employer or a payment processor, and it is not a
            party to any agreement made between users. Every deal, delivery and
            payment happens directly between the people involved.
        </p>

        <h2>No vouching</h2>
        <p>
            The platform does not approve who may trade and does not vouch for
            any user, listing, product or service. Use your own judgement before
            you commit to anything.
        </p>

        <h2>Verification is a record, not a guarantee</h2>
        <p>
            A verified badge means an independent volunteer visited a site and
            recorded what they found, using a questionnaire, notes and
            photographs. It is one person's account of one visit on one day,
            published under that volunteer's name. It is context to weigh up —
            not an inspection, a certification or a promise by the platform.
        </p>

        <h2>No warranty</h2>
        <p>
            The platform and its content are provided "as is" and "as
            available", without warranties of any kind to the extent permitted
            by law, including as to accuracy, availability or fitness for a
            particular purpose.
        </p>

        <h2>Third-party content and links</h2>
        <p>
            Listings, profiles and stories are posted by users, who are
            responsible for them. Where the platform links to something outside
            it, that content and those sites are not under our control and we
            are not responsible for them.
        </p>

        <h2>No professional advice</h2>
        <p>
            Nothing on the platform is legal, financial, medical or
            professional advice. If you need advice, consult a qualified
            professional.
        </p>

        <h2>Limitation of liability</h2>
        <p>
            To the maximum extent permitted by law, {{ $entity }} is not liable
            for loss or damage arising from your use of the platform, from any
            dealing between users, or from any inaccurate, incomplete or
            misleading content posted by a user, including a verification
            record.
        </p>

        <h2>Contact</h2>
        <p>
            Questions about this disclaimer: {{ $ph($contact, 'contact email') }}.
        </p>
    </section>
@endsection
