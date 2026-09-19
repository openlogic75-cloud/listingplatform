# DPDP & production readiness checklist

> Status of this project against India's Digital Personal Data Protection Act,
> 2023, and the code-side readiness for a production launch. Owner decisions
> still outstanding are listed at the end.

## DPDP obligations → where they are met

| Obligation | Implementation |
|---|---|
| Lawful purpose, and notice before collection | Legal pages (Terms, Privacy, Disclaimer) + consent notice `docs/dpdp/consent-notice.md`; registration requires an explicit acceptance checkbox. |
| Consent recorded with version + time | `consents` rows written at registration, booking, errand and notifications, each with `text_version`, `granted_at`. |
| Withdraw consent | Consent revoke endpoint; profile screens in app + web. |
| Right to access | Self-serve JSON export (`/export`, `DataExportService`). |
| Right to correction | Profile edit in app + web. |
| Right to erasure | Self-serve account deletion (`/deletion`, `DataDeletionService`) with end-to-end anonymisation; integrity snapshots keep no contact data. |
| Right to nominate | Handled through the grievance officer (record a nominee on request). |
| Grievance redressal | Grievance officer named in `config/legal.php`, shown on the Privacy and Terms pages. |
| Children's data | 18+ statement in Terms/Privacy; no knowing collection. |
| Data minimisation | Buyers never register; guest bookings keep name + phone only. |
| Security safeguards | Encrypted PII at rest, keyed blind indexes for lookups, hashed passwords, HTTPS/HSTS, strict CSP, rate limits, role-scoped access. |
| Breach notification | Process in `docs/security/checklist.md`; notify the Data Protection Board and affected users as required. |
| Storage limitation | Scheduled retention sweeps (`retention:sweep`) for stale guest bookings, orphaned media, expired consents, deletion follow-ups. |
| Purpose limitation / sharing | Privacy Policy §7 — never sold, never for ads, shared only between parties and with hosting providers. |

## Code-side production readiness

- `APP_DEBUG=false`, database session/cache/queue drivers, security headers, token CORS (`backend/.env.production.example`).
- Stylesheets published with `php artisan assets:publish`; asset URLs versioned so browsers never serve a stale file.
- 240 backend tests green; Flutter `analyze` clean, tests green.
- Deploy runbook, security checklist and launch checklist in `docs/`.

## Still required from the owner (blockers)

1. **Legal identity** — set `LEGAL_ENTITY_NAME`, `LEGAL_ADDRESS`,
   `LEGAL_CONTACT_EMAIL`, `GRIEVANCE_OFFICER_NAME`,
   `GRIEVANCE_OFFICER_EMAIL`, `LEGAL_JURISDICTION` in the production `.env`.
   Until set, the pages show a clearly-marked `[...not yet configured]` token.
2. **Q7 — Hostinger confirmation**: PHP version pin and the single cron entry
   (`* * * * * php artisan schedule:run`) so retention sweeps run automatically.
3. **Q13 — Brand**: name/logo/palette, needed for the favicon, social image and
   store listing assets.
4. **Q17 — `vendor/` handling** for the deploy flow (commit or `.gitignore` +
   `composer install`).
5. **Deploy itself** has not been run (M8.4).
6. **App store build**: a machine with the Android SDK plus a signing keystore;
   `FCM_SERVER_KEY` set on the backend for push (currently inbox-only).

## Notes

- Verification is an on-site visit record by an independent volunteer, not a
  platform guarantee (stated on the listing page, About and Disclaimer).
- The platform is not a party to any deal and never moves money.
