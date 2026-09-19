@extends('layouts.app')

@section('title', "About - ".config('branding.name'))

@section('metaDescription', 'A connector, not a gatekeeper: it helps farmers, sellers, logistics, resellers and skilled workers reach each other and end customers. No commission, no payments, no vouching.')

@php
    $contactEmail = config('legal.contact_email');
    $grievanceOfficer = config('legal.grievance_officer_name');
    $grievanceEmail = config('legal.grievance_officer_email');
@endphp

@section('content')
    <section class="container hero">
        <div>
            <h1>What this platform is for</h1>
            <p>
                It is a connector. It helps farmers and sellers, the logistics
                that move goods, resellers and skilled workers find each other
                and reach end customers. That is all it sets out to do.
            </p>
            <p>
                It does not sell anything itself, it is not a party to any deal,
                and it does not vouch for anyone. The people who meet here deal
                with each other directly. The platform is open source, free to
                use, funded by donations, takes no commission and handles no
                payments.
            </p>
            <a class="btn btn-primary" href="{{ route('donation') }}">Donate via UPI</a>
            <a class="btn btn-secondary" href="{{ route('catalog') }}">See the catalog</a>
        </div>
        <img class="hero-art" src="{{ asset('img/empty.svg') }}" alt="Illustration of people working together" width="560" height="420">
    </section>

    <section class="section section-alt">
        <div class="container">
            <h2>Who it helps</h2>
            <div class="value-grid">
                <div class="card">
                    <h3>Farmers and sellers</h3>
                    <p>Traditional and agro vendors, and rental or homestay owners, list their items with prices and minimum order quantities, manage their shop, see incoming bookings and their own sales records, and can set up referral codes. Sellers publish in the areas an administrator has opened for service.</p>
                </div>
                <div class="card">
                    <h3>Buyers</h3>
                    <p>End customers browse and search without creating an account. They book with a name and a phone number, see the minimum order before ordering, and can look a booking up later with its code and their phone number. Every arrangement, payment and delivery is between the buyer and the seller.</p>
                </div>
                <div class="card">
                    <h3>Resellers</h3>
                    <p>Resellers use the same guest checkout to order in bulk, and their bookings are marked as reseller orders so the seller knows they are supplying someone who will sell on. The terms are theirs to agree.</p>
                </div>
                <div class="card">
                    <h3>Logistics — collectors and drivers</h3>
                    <p>Collectors pick up from sellers; delivery drivers take the goods on. Drivers declare a base of one district plus up to five localities, set themselves online or offline, and see only the jobs that match where they work. Anyone can also request a one-off errand.</p>
                </div>
                <div class="card">
                    <h3>Skilled workers</h3>
                    <p>Electricians, plumbers, carpenters, mechanics and other trades list the work they provide and appear in a public directory that buyers can search by trade. What they charge and agree is between them and the customer.</p>
                </div>
                <div class="card">
                    <h3>Verification volunteers</h3>
                    <p>Volunteers visit a site, record what they found with photos, and file a report. They keep the visit fee as their allowance and gain first-hand experience. Once an administrator approves the report, the listing shows that a visit took place and who made it.</p>
                </div>
            </div>
        </div>
    </section>

    <section class="section">
        <div class="container">
            <h2>What you can do here</h2>
            <div class="value-grid">
                <div class="card">
                    <h3>Browse and search</h3>
                    <p>A catalog of traditional products, agro produce and rentals, with filters for area and price. A dedicated PG, rentals and homestays section, and public directories for skilled workers and for transport and errands.</p>
                </div>
                <div class="card">
                    <h3>Book without an account</h3>
                    <p>Guests book directly from a listing. Minimum order quantities are checked automatically, and the seller contacts the buyer on the phone number given.</p>
                </div>
                <div class="card">
                    <h3>Request transport and errands</h3>
                    <p>Vendors can request a pickup or delivery for a booking; anyone can post an errand. Drivers run from their declared localities and see matching jobs.</p>
                </div>
                <div class="card">
                    <h3>An on-site visit on record</h3>
                    <p>A listing can carry the outcome of a volunteer's visit: what they saw, with photos, and who made the visit. It is one person's observation on one day — useful context to weigh up, not a guarantee from us.</p>
                </div>
                <div class="card">
                    <h3>Referrals and affiliate marketing</h3>
                    <p>Vendors and drivers create shareable codes and set the commission they are willing to pay. When an order completes through a code, the owner reviews it and approves the commission themselves — then settles it directly with the marketer.</p>
                </div>
                <div class="card">
                    <h3>Sales records</h3>
                    <p>Vendors see completed-order totals by month, quarter and year, and can download a PDF report for their own bookkeeping.</p>
                </div>
                <div class="card">
                    <h3>Support the platform</h3>
                    <p>Donations are collected through a UPI ID and QR code set by an administrator. The platform never stores payment details.</p>
                </div>
                <div class="card">
                    <h3>Stories</h3>
                    <p>The blog introduces new businesses and farms, and links straight to the ones it features.</p>
                </div>
            </div>
        </div>
    </section>

    <section class="section section-alt">
        <div class="container">
            <h2>What we do not do</h2>
            <div class="value-grid">
                <div class="card">
                    <h3>We do not vouch for anyone</h3>
                    <p>The platform does not approve who may trade, does not guarantee anyone's goods or conduct, and does not stand behind a deal. Use your own judgement; we only help you find each other.</p>
                </div>
                <div class="card">
                    <h3>We are not a party to any deal</h3>
                    <p>Every sale, booking, delivery, job and payment is directly between the people involved. The platform takes no responsibility for whether it goes well.</p>
                </div>
                <div class="card">
                    <h3>No commission</h3>
                    <p>The platform takes no cut from any sale, booking or delivery — from anyone, including the platform's own referral codes.</p>
                </div>
                <div class="card">
                    <h3>No transactions on the platform</h3>
                    <p>Money settles directly between the people involved, offline. The platform only records fulfilment status and keeps no payment credentials.</p>
                </div>
                <div class="card">
                    <h3>No forced accounts</h3>
                    <p>Buyers never register. Only sellers, logistics, skilled workers and volunteers do, and their personal data is encrypted and can be exported or deleted on request.</p>
                </div>
                <div class="card">
                    <h3>No location tracking</h3>
                    <p>There are no map integrations, no live GPS and no ETAs. Logistics is coordinated by the localities a driver has declared they work in.</p>
                </div>
            </div>
        </div>
    </section>

    <section class="section">
        <div class="container">
            <h2>Contact and grievance redressal</h2>
            <p>
                For questions about Shekuthi, contact
                <a href="mailto:{{ $contactEmail }}">{{ $contactEmail }}</a>.
                Grievances can be addressed to {{ $grievanceOfficer }} at
                <a href="mailto:{{ $grievanceEmail }}">{{ $grievanceEmail }}</a>.
            </p>
            <a class="btn btn-secondary" href="{{ route('contact') }}">Contact Shekuthi</a>
        </div>
    </section>

    <section class="section section-alt">
        <div class="container">
            <h2>Open source</h2>
            <p>
                This platform is open source. The full source code is public and
                free to use under the MIT licence: read it, run it, adapt it, or
                build your own version from it. If you want to contribute, you
                are welcome to.
            </p>
            <p>
                Report a bug, suggest a change, or open a pull request on the
                repository. Contributions are reviewed like anyone else's work,
                and the same standards of privacy and honest dealing apply to
                the code as to the platform.
            </p>
            <a class="btn btn-primary" href="{{ config('branding.repository_url') }}"
               target="_blank" rel="noopener noreferrer">View the source and contribute</a>
        </div>
    </section>

    <section class="section section-alt">
        <div class="container">
            <h2>Built with AI</h2>
            <p>
                This project was built with the help of AI coding tools: the
                opencode agent, using the GLM and DeepSeek language models.
            </p>
            <p>
                It is a disclosure, not a claim of correctness. AI-assisted code
                still has to pass the same tests, review and checks as anything
                else, and mistakes are possible. If you spot one, report it on
                the repository.
            </p>
        </div>
    </section>
@endsection
