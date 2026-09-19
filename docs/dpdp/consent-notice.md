# Consent notice (DPDP Act, 2023)

> **Version 1.0** · Purpose of this file: the exact wording shown to a person
> before we collect their personal data, and how we record that they agreed.
> Bump the version here and in `LEGAL_CONSENT_VERSION` whenever the wording
> changes — every consent row stores the version that was shown.

## Notice shown at registration (registerable roles)

> To create your account we need your name, email, phone number and a
> password. We use them to sign you in, to show the shop or services you
> choose to list, and to put you in touch with the people you trade with.
> Your personal fields are stored encrypted. You can download or delete your
> data at any time from your profile.
>
> I am 18 or older, and I accept the [Terms & Conditions](/terms) and the
> [Privacy Policy](/privacy).

Accepted by a required checkbox. The platform records the acceptance with the
notice version and the time it was given (`consents` table, key
`registration`).

## Notice shown for a guest booking or errand

> We ask for your name and phone number so the seller (or the assigned driver)
> can reach you about this booking. They are used only for that, and are
> stored encrypted.

`consents` key `booking_contact` / `errand_contact`, written when the booking
or errand is created.

## Notice shown for notifications

> Turn on notifications to get booking updates, job offers and verification
> results on this device. You can turn them off at any time.

`consents` key `notifications`, written when a device token is registered.

## How consent is recorded

Each row in `consents` holds:

- `subject_type` / `subject_id` — who it is about,
- `consent_key` — the purpose,
- `text_version` — the version of the notice shown,
- `purpose` — a one-line summary,
- `granted_at` — when it was given,
- `revoked_at` — when it was withdrawn.

The version lets us reproduce the exact text a person agreed to.

## Withdrawing consent

Withdrawal is available in the app and on the website (profile → consent), and
by contacting the grievance officer. Withdrawing may mean the related part of
the service can no longer be provided. Withdrawal does not affect processing
already carried out.

## What we do not need consent for

Security, fraud prevention, and processing data a person has themselves made
public by listing it — the limited legitimate uses the Act allows.
