@extends('layouts.app')

@section('title', 'Terms & Conditions - '.config('branding.name'))
@section('metaDescription', 'The terms that govern use of this marketplace: a connector platform with no commission, no on-platform payments and no vouching.')

@php
    $entity = config('legal.entity_name');
    $version = config('legal.terms_version');
    $effective = config('legal.effective_date');
    $contact = config('legal.contact_email');
    $officer = config('legal.grievance_officer_name');
    $officerEmail = config('legal.grievance_officer_email');
    $jurisdiction = config('legal.jurisdiction');
    $ph = fn (?string $v, string $label): string => $v !== null && $v !== '' ? $v : '['.$label.' not yet configured]';
@endphp

@section('content')
    <section class="container section" style="max-width: 820px;">
        <h1>Terms &amp; Conditions</h1>
        <p class="muted small">Version {{ $version }} · Effective {{ $effective }}</p>

        <p>
            These terms govern your use of {{ $entity }} ("the platform"). By
            using the platform, or by registering an account, you agree to them.
            If you do not agree, do not use the platform.
        </p>

        <h2>1. What the platform is</h2>
        <p>
            The platform is a connector. It helps sellers, buyers, resellers,
            logistics providers, skilled workers and verification volunteers
            find each other. It does not buy or sell goods, does not employ the
            people who use it, is not a party to any deal, and does not vouch
            for anyone. Every transaction, agreement, delivery and payment is
            made directly between the people involved.
        </p>

        <h2>2. Eligibility</h2>
        <p>
            You must be {{ config('legal.minimum_age') }} years or older to use
            the platform. Buyers use the platform as guests and do not register.
            Sellers, drivers, collectors, skilled workers and verification
            volunteers register. By registering you confirm the information you
            give is accurate and that you are legally able to provide the goods
            or services you offer.
        </p>

        <h2>3. Accounts</h2>
        <ul>
            <li>Keep your login credentials secure; you are responsible for activity under your account.</li>
            <li>Provide accurate details and keep them up to date. Some roles are subject to administrator approval.</li>
            <li>We may suspend or close an account that breaks these terms or the law.</li>
        </ul>

        <h2>4. What may be listed</h2>
        <p>
            You may list lawful local goods, agro produce, rentals and
            homestays, and offer lawful transport, errand and skilled work. You
            may not list or offer anything illegal, unsafe, counterfeit,
            stolen, or prohibited in the area where you operate, and you may
            not misrepresent what you are offering. You are responsible for the
            accuracy of your listings and for complying with the law that
            applies to you.
        </p>

        <h2>5. Bookings and dealings</h2>
        <p>
            Buyers book directly with a seller, submitting a name and contact
            number. Minimum order quantities shown on a listing are checked by
            the platform before a booking is accepted. Payment and delivery are
            settled directly between the parties, offline. The platform is not
            a party to that arrangement and does not guarantee payment,
            quality, delivery or that anyone will perform.
        </p>

        <h2>6. Logistics, errands and skilled work</h2>
        <p>
            Drivers, collectors and skilled workers are independent. Nothing
            here creates an employment relationship with the platform.
            Logistics is coordinated by the localities a driver has declared
            they work in; there is no live tracking, mapping or estimated
            arrival time. Work terms, rates and safety arrangements are between
            the parties.
        </p>

        <h2>7. Verification</h2>
        <p>
            Verification is an optional, paid on-site visit performed by an
            independent volunteer, using a questionnaire the platform provides.
            The volunteer's recorded answers and photographs are published as a
            signed story, and the resulting badge names the volunteer who made
            the visit. This is one person's record of one visit: it is context
            to weigh up, not a guarantee by the platform. The volunteer's fee
            is paid to the volunteer directly by the party requesting the
            verification; the platform takes nothing.
        </p>

        <h2>8. Referrals and commissions</h2>
        <p>
            Sellers and drivers may create referral codes and set the
            commission they are willing to pay a marketer. Commission is
            recorded by the platform but paid directly between those parties,
            and the code owner decides whether to approve each recorded
            conversion. The platform takes no commission and handles no
            payments.
        </p>

        <h2>9. Donations</h2>
        <p>
            The platform runs on voluntary donations, collected through a UPI ID
            and QR code displayed on the donation page. Donations are voluntary
            and non-refundable and are not payment for any good or service. The
            platform never stores payment credentials.
        </p>

        <h2>10. Acceptable use</h2>
        <ul>
            <li>Do not break the law, infringe anyone's rights, or post unlawful, abusive or misleading content.</li>
            <li>Do not attempt to disrupt, overload, scrape at scale, or gain unauthorised access to the platform.</li>
            <li>Do not impersonate anyone or misrepresent your identity or role.</li>
        </ul>

        <h2>11. Content you post</h2>
        <p>
            You keep ownership of the content you post. You grant the platform a
            non-exclusive, royalty-free licence to store, display and share that
            content as needed to run the service — for example, showing a
            listing, or publishing a verification story. You are responsible for
            having the right to post it, including any photos of people or
            places.
        </p>

        <h2>12. Our content</h2>
        <p>
            The platform's own code and written content are provided on an open
            basis; the name, logo and brand assets of the operator remain the
            operator's property.
        </p>

        <h2>13. Disclaimers and limitation of liability</h2>
        <p>
            The platform is provided "as is" and "as available", without
            warranties of any kind, express or implied, to the extent permitted
            by law. To the maximum extent permitted by law, {{ $entity }} is not
            liable for indirect, incidental or consequential losses, or for
            losses arising from any dealing, delivery, payment, listing or
            verification between users. Where liability cannot be excluded, it
            is limited to the total donations, if any, you have made in the
            twelve months before the claim.
        </p>

        <h2>14. Indemnity</h2>
        <p>
            You agree to indemnify {{ $entity }} against claims, losses and
            costs arising from your use of the platform, your content, or your
            breach of these terms or the law.
        </p>

        <h2>15. Ending use</h2>
        <p>
            You may stop using the platform at any time and may delete your
            account from your profile. We may suspend or end access if these
            terms are broken or if required by law.
        </p>

        <h2>16. Changes to these terms</h2>
        <p>
            We may update these terms. Material changes will be reflected by a
            new version and effective date on this page; continued use after a
            change means you accept it.
        </p>

        <h2>17. Governing law</h2>
        <p>
            These terms are governed by the laws of {{ $jurisdiction }}, and the
            courts there have exclusive jurisdiction, subject to any mandatory
            rights you have under applicable law.
        </p>

        <h2>18. Contact</h2>
        <p>
            Questions about these terms: {{ $ph($contact, 'contact email') }}.
            Grievances: {{ $ph($officer, 'grievance officer name') }} at
            {{ $ph($officerEmail, 'grievance officer email') }}.
        </p>
    </section>
@endsection
