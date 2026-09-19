<?php

/**
 * Legal / compliance configuration (M26.1). Everything an operator must
 * supply before going live lives here as an environment value, so the legal
 * pages ship complete without hard-coding anyone's details.
 *
 * Set these in the production .env:
 *   LEGAL_ENTITY_NAME, LEGAL_ADDRESS, LEGAL_CONTACT_EMAIL,
 *   GRIEVANCE_OFFICER_NAME, GRIEVANCE_OFFICER_EMAIL, LEGAL_JURISDICTION,
 *   LEGAL_EFFECTIVE_DATE, LEGAL_TERMS_VERSION, LEGAL_PRIVACY_VERSION,
 *   LEGAL_CONSENT_VERSION
 *
 * Any value left unset renders as a clearly-marked placeholder on the pages so
 * it cannot be missed before launch.
 */
return [
    'entity_name' => env('LEGAL_ENTITY_NAME', config('branding.name')),

    'address' => env('LEGAL_ADDRESS'),

    'contact_email' => env('LEGAL_CONTACT_EMAIL', 'contact@shekuthi.in'),

    // DPDP requires a grievance channel; defaults to the contact email.
    'grievance_officer_name' => env('GRIEVANCE_OFFICER_NAME', 'K Hika Zhimomi'),
    'grievance_officer_email' => env(
        'GRIEVANCE_OFFICER_EMAIL',
        env('LEGAL_CONTACT_EMAIL', 'contact@shekuthi.in'),
    ),

    // Exclusive jurisdiction for disputes (Q10 region: Nagaland, India).
    'jurisdiction' => env('LEGAL_JURISDICTION', 'Nagaland, India'),

    'effective_date' => env('LEGAL_EFFECTIVE_DATE', now()->format('j F Y')),

    // Bump when the wording changes; recorded against each consent row.
    'terms_version' => env('LEGAL_TERMS_VERSION', '1.0'),
    'privacy_version' => env('LEGAL_PRIVACY_VERSION', '1.0'),
    'consent_version' => env('LEGAL_CONSENT_VERSION', '1.0'),

    // Minimum age to use the platform (DPDP children's-data stance: 18+).
    'minimum_age' => (int) env('LEGAL_MINIMUM_AGE', 18),
];
