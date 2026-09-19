# Privacy policy (DPDP) — consent text v1.0

> This is the canonical copy rendered at `/privacy`. When the text changes,
> bump `BookingService::CONSENT_TEXT_VERSION` and any other version constant
> that stores `text_version` on consent rows. Keep this file and the blade
> page in sync.

## What we collect

- **Registered users** (vendors, drivers, collectors, skilled workers, volunteers):
  name, email, phone, password (hashed), role, and role-specific fields
  (base of operation, availability).
  If you are a **driver**, the contact phone you add to your work profile is
  shown publicly in the Transport & errands directory so buyers can call you
  directly; remove it there to stop being callable.
- **Guests** (booking / errand): contact name and phone only, kept for the
  purpose stated at the moment of collection.
- **Device tokens** when you enable app notifications (your push token, not
  your contacts).

## Why we hold it

- To operate the marketplace: take bookings against listings, assign
  logistics jobs and errands, run site visits and issue verified badges.
- To contact you about a booking or delivery against that booking.
- To issue a verified badge, we keep the volunteer's name as an immutable
  snapshot so the attribution survives a later deletion request.

## Your rights

- **Export**: request a machine-readable copy of everything we hold about
  you at any time (self-serve in the app; audit records kept).
- **Deletion**: request full anonymization of your personal data
  (self-serve in the app). We keep only integrity snapshots with no personal
  data — verified-badge volunteer names and an anonymized ledger.
- **Consent**: any consent you gave can be revoked in the app; revocation is
  recorded with a timestamp.

## What we never do

- Never sell, rent, or share your data with advertisers.
- Never process, store, or pass along payment credentials — settlements
  happen directly between you and the other party, offline.

## Retention

Guest booking contacts and unfinished drafts are swept on a schedule;
expired consents are flagged for review. Retention work is recorded in the
tracker (M7.5) and follows the sweep rules there.

## Contact

For any privacy question or request, contact the operator listed on this
site. Requests are logged in `data_requests` with their outcome.