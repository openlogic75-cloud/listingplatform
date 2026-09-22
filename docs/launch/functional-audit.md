# Functional Causal Audit

Audit scope: trace each major product promise through **data/schema → logic →
API/web/app surface → automated evidence**. This is a code-and-test audit, not
a substitute for authenticated production and physical-device walkthroughs.

## Evidence baseline

- Backend: 255 tests passed, 958 assertions.
- Flutter: `flutter analyze` clean, 19/19 tests passed.
- Public live checks: `/`, `/api/v1/locations`, `/api/v1/posts`,
  `/api/v1/stays` and `/api/v1/reseller-produce` returned HTTP 200.
- Android APK: built for Shekuthi API/site; physical-device testing remains
  pending.

## Working causal chains

| Area | Data/logic | API/web/app | Evidence | Status |
|---|---|---|---|---|
| Listings and bookings | Product, booking, MOQ and status-transition services | Catalog, listing detail, guest booking, vendor booking dashboard/app repositories | Backend feature tests and repository tests | Working in code; production smoke test pending |
| Farm-produce collections | `collector_assignments`, hub districts, `collect_produce` jobs and collector matcher | Vendor web/app request, collector app accept/progress | `CollectionJobTest`, `CollectorAssignmentTest`, collector repository tests | Working in code |
| Legal registration consent | Consent rows, notice versions and registration service | Web/app registration and legal pages | Legal compliance tests | Working in code; production legal identity pending |
| Public content | Posts/stays/directories controllers and resources | Website pages and app repositories/readers | Blog, stay and directory tests | Working in code |
| Media uploads | Shared validator, 2 MB cap, resize/WebP, media records | Web/app listing and evidence upload paths | Media upload tests | Backend path working; device evidence capture remains incomplete |

## Open functional gaps

- **DPDP export/deletion:** current services do not yet cover every personal-data
  surface such as all bookings, errands, referrals, verifications/media, device
  tokens and notifications. See M27.4 and M27.6.
- **Consent coverage:** errand contact and notification registration still need
  consent rows. See M27.5.
- **Volunteer evidence:** the backend accepts evidence paths, but the Flutter
  report flow does not yet pick/upload evidence photos. See M25.3/M27.3.
- **Verification story app link:** the API/app does not yet expose/open the
  verification story slug from a listing. See M27.7.
- **Ordinary pickup/delivery:** the farm-produce collection client exists, but
  the normal booking-linked vendor pickup request remains open. See M13.3/M27.2.
- **App parity:** listing edit, admin listing moderation, web notifications and
  app affiliate UI remain tracked gaps. See M13/M14/M21.
- **Production operations:** Hostinger PHP/extension configuration, cron,
  SMTP, FCM, backups, legal identity and real admin/region data require live
  verification. See Q7, M32 and the launch checklist.

## Device/browser verification still required

- Android phones with gesture navigation and three-button navigation: verify
  the bottom NavigationBar safe area, Back action and keyboard/input screens.
- Android photo permissions, upload failure/retry, collection actions and
  notification delivery.
- Website narrow widths at 320px, 375px, 768px and desktop widths.
- Authenticated production smoke tests for registration, login, admin, booking,
  upload, deletion and collector flows.

**Verdict:** the implemented core flows have causal code paths and automated
coverage, but the project is not fully production-ready until the open gaps and
live/device checks above are closed.
