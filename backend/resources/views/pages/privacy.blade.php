@extends('layouts.app')

@section('title', 'Privacy Policy - '.config('branding.name'))
@section('metaDescription', 'How this platform collects, uses, shares and protects personal data, and the rights you have under India\'s Digital Personal Data Protection Act, 2023.')

@php
    $entity = config('legal.entity_name');
    $version = config('legal.privacy_version');
    $effective = config('legal.effective_date');
    $contact = config('legal.contact_email');
    $officer = config('legal.grievance_officer_name');
    $officerEmail = config('legal.grievance_officer_email');
    $ph = fn (?string $v, string $label): string => $v !== null && $v !== '' ? $v : '['.$label.' not yet configured]';
@endphp

@section('content')
    <section class="container section" style="max-width: 820px;">
        <h1>Privacy Policy</h1>
        <p class="muted small">
            Version {{ $version }} · Effective {{ $effective }} · This notice is
            given under India's Digital Personal Data Protection Act, 2023
            ("DPDP Act").
        </p>

        <p>
            {{ $entity }} ("we") operates this marketplace, on the website and
            in the mobile app. For the personal data described here, we act as
            the Data Fiduciary. We keep data collection to the minimum needed to
            run the service, and we do not sell personal data.
        </p>

        <h2>1. Who to contact</h2>
        <p>
            Privacy questions and grievances: {{ $ph($officer, 'grievance officer name') }},
            {{ $ph($officerEmail, 'grievance officer email') }}.
            @if ($contact && $contact !== $officerEmail)
                General contact: {{ $contact }}.
            @endif
            @if (config('legal.address'))
                Address: {{ config('legal.address') }}.
            @endif
        </p>

        <h2>2. Personal data we collect</h2>
        <ul>
            <li><strong>Registered users</strong> (sellers, drivers, collectors, skilled workers, volunteers): name, email, phone number, a hashed password, your role, and role-specific details such as your district and, for drivers, a base of operation.</li>
            <li><strong>Guest bookings and errands</strong> (buyers, who never register): the contact name and phone number you provide, plus any notes you add.</li>
            <li><strong>Listings and profiles</strong> you create, and images you upload (listings, shop details, a volunteer profile photo).</li>
            <li><strong>Verification visits</strong>: the volunteer's report, checklist answers, notes, photographs and, if given, a location point for the site.</li>
            <li><strong>Usage and device data</strong> needed to operate the service securely, such as authentication tokens and, if you enable notifications, a device push token.</li>
        </ul>
        <p>
            We do not ask for government identifiers, and we do not collect
            payment credentials — payments never pass through the platform.
        </p>

        <h2>3. Why we use it</h2>
        <ul>
            <li>To create and secure your account and let you sign in.</li>
            <li>To publish what you choose to list and connect you with the other party.</li>
            <li>To let a seller reach a buyer about a booking, and support guest lookups by code and phone.</li>
            <li>To coordinate transport and errands through the areas a driver declares.</li>
            <li>To record and display verification visits and their outcomes.</li>
            <li>To send you service notifications you have asked for.</li>
            <li>To keep the service safe, prevent abuse, and meet legal obligations.</li>
        </ul>
        <p>
            We process personal data on the basis of your consent, and for the
            limited legitimate uses the DPDP Act permits (for example,
            security, and where you have made data public by listing it).
        </p>

        <h2>4. Consent</h2>
        <p>
            Where we rely on consent, we ask for it clearly before we collect
            the data, tell you the purpose in plain language, and record the
            consent with the version of the notice you saw and the time you
            gave it. You can withdraw consent at any time from your profile or
            by contacting us; withdrawing may mean we can no longer provide the
            related part of the service.
        </p>

        <h2>5. Guests and contact details</h2>
        <p>
            Buyers use the platform as guests. The contact name and phone number
            you give for a booking or errand are used only to coordinate that
            booking or errand, and are shared only with the seller or the
            assigned driver. They are stored encrypted.
        </p>

        <h2>6. What is shown publicly</h2>
        <p>
            Some fields are public by design and visible to anyone:
        </p>
        <ul>
            <li>A seller's shop name, listings and descriptions.</li>
            <li>A driver's name, area and the transport categories they choose, along with the contact number they add to their work profile so buyers can call them.</li>
            <li>A skilled worker's name, area and the trades they list.</li>
            <li>A volunteer's name and photo as it appears on a verification badge and its story.</li>
        </ul>
        <p>
            You control these fields: change or remove them in your profile, and
            they stop being shown.
        </p>

        <h2>7. Sharing</h2>
        <p>
            We never sell personal data and we do not share it for advertising.
            We share it only:
        </p>
        <ul>
            <li>between the parties to a booking, errand or job, to the extent needed to carry it out;</li>
            <li>with service providers who host and run the platform for us, bound to use it only for that purpose;</li>
            <li>where the law requires it, or to protect rights and safety.</li>
        </ul>

        <h2>8. How long we keep it</h2>
        <p>
            We keep personal data only as long as needed for the purpose it was
            collected, or as the law requires. Guest booking and errand contact
            details, unused media and expired consents are cleared by scheduled
            retention sweeps. When you delete your account, we remove or
            anonymise your personal data; integrity records that name other
            people (such as a verification badge's volunteer name) are kept
            only as a snapshot, without contact details.
        </p>

        <h2>9. Security</h2>
        <p>
            Personal data is encrypted in transit and at rest, sensitive fields
            use encryption with separate lookup indexes, passwords are stored
            only as hashes, and access is limited by role. If a personal data
            breach occurs, we will notify the Data Protection Board of India and
            affected users as the DPDP Act requires.
        </p>

        <h2>10. Where your data is stored</h2>
        <p>
            We aim to keep personal data within India. Where any processing
            happens outside India, we do so only as the DPDP Act and any
            government restriction permits.
        </p>

        <h2>11. Your rights</h2>
        <p>
            Under the DPDP Act you have the right to:
        </p>
        <ul>
            <li><strong>Access</strong> a summary of your personal data and how it is processed. Export a machine-readable copy from your profile.</li>
            <li><strong>Correct</strong> and keep your data accurate — edit your profile at any time.</li>
            <li><strong>Erase</strong> your data — delete your account from your profile; we carry it out end to end.</li>
            <li><strong>Withdraw consent</strong> for any purpose you previously agreed to.</li>
            <li><strong>Nominate</strong> someone to exercise these rights on your behalf in the event of death or incapacity — contact our grievance officer to record a nominee.</li>
            <li><strong>Complain</strong> — raise a grievance with our grievance officer first, and you may approach the Data Protection Board of India if you are not satisfied.</li>
        </ul>
        <p>
            We will respond to a request within the time the DPDP Act allows.
            Export and deletion are available directly in the app and on the
            website.
        </p>

        <h2>12. Children</h2>
        <p>
            The platform is for people aged {{ config('legal.minimum_age') }} and
            over. We do not knowingly collect personal data of children. If we
            learn that we have, we will delete it. If you believe a child has
            given us data, contact the grievance officer.
        </p>

        <h2>13. Cookies and local storage</h2>
        <p>
            We use only what the service needs to work: a session cookie to keep
            you signed in on the website, and secure local storage for your
            sign-in token in the app. We do not use advertising or cross-site
            tracking cookies, and we run no third-party analytics.
        </p>

        <h2>14. Changes</h2>
        <p>
            If this notice changes, we publish a new version and effective date
            here, and where the change is material we ask for your consent
            again.
        </p>

        <h2>15. Contact</h2>
        <p>
            {{ $ph($officer, 'grievance officer name') }} ·
            {{ $ph($officerEmail, 'grievance officer email') }}.
        </p>
    </section>
@endsection
