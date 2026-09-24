# Shekuthi Functional Causal Audit

Audit updated 2026-09-24. Method: trace product requirement → database/model →
service/controller/API → website/app surface → automated evidence. Code presence
and passing tests do not by themselves prove a live authenticated/device flow.

## Verification snapshot

- Backend: **259 tests passed / 976 assertions** (`php artisan test`).
- Flutter: **analyze clean / 19 tests passed**.
- Public live endpoints checked 2026-09-24 returned HTTP 200: `/`, `/about`,
  `/contact`, `/privacy`, `/terms`, `/disclaimer`, `/catalog`, `/stays`,
  `/reseller-produce`, `/workers`, `/transport`, `/blog`, `/api/v1/locations`,
  `/api/v1/posts`, `/api/v1/stays`, `/api/v1/reseller-produce`.
- Current M45 APK is a 57 MB sideload build targeting `https://shekuthi.in` and
  includes Android INTERNET permission; it is debug-signed and not Play Store
  ready. Bottom safe-area behavior has not yet been walked on physical devices.

## Function inventory: implemented chains

| Function | Data / logic | API / website / app surface | Evidence and limits |
|---|---|---|---|
| Account registration | `RegistrationService`, encrypted email/phone, blind indexes, password hash, registration consent | Web and API register; email verification signed link; mobile pending-verification state; resend verification endpoints | `RegistrationTest`, `WebRegistrationTest`, `EmailVerificationTest`; SMTP delivery must be confirmed in production |
| Email-link verification | `MustVerifyEmail`, signed URL validation, configurable production verification gate | Web verification/resend routes; API resend endpoint; mobile pending state and descriptive auth errors | Automated verification test passes; still confirm signed-link delivery and click on production mailbox/device |
| Listing moderation and image consent | Pending product status, admin decision, consent record for public listing imagery | Vendor submits/edits listing; admin approves/rejects; catalogs serve active listings only | `ListingApprovalTest`; live admin moderation and actual public-photo consent UX need production walkthrough |
| Authentication/session | role/active checks, volunteer approval, collector assignment, verified-email gate | Web sessions; Sanctum API tokens; admin login separate; logout/profile endpoints | Auth tests and role-specific tests; Sanctum tokens currently have no configured expiry |
| Vendor profile/listings | Vendor/Product models, shared FormRequests, ProductPolicy, 2 MB upload validator, 1600px resize/WebP, four-photo cap | Web dashboard and Flutter listing create; API CRUD; pending moderation; explicit public-image consent; admin `/admin/listings` approve/reject; public catalog only active | `ListingsTest`, `WebVendorListingTest`, `ListingApprovalTest`, `MediaUploadTest`; Flutter listing edit screen is not wired/implemented |
| Public discovery | Active-only products, search/category/area/price API filters | Website `/catalog`, `/stays`, `/reseller-produce`; public vendor, worker, transport, stories pages; app catalog/stays/farm feeds | Catalog/stay/reseller/directory/blog tests; general web `/catalog` still lacks area/price filters |
| Guest commerce | MOQ/stock checks, snapshots, booking lifecycle, guest consent | Listing detail booking form; code+phone lookup/cancel; vendor booking dashboard and app | Booking/WebBooking/WebVendorBooking tests; normal vendor-requested pickup/delivery UI is absent |
| Drivers and errands | Driver base, online state, locality job matching, errand lifecycle | Web/app driver base and profile; driver jobs/errands screens; guest errand create/lookup; online-driver counts | Logistics, driver base, errand and directory tests; real driver handoff not live-tested |
| Collectors and farm produce | `collector_assignments`, one per locality and user; hub districts; `collect_produce` logistics job matching/status | Admin assignment; vendor collection forms web/app; collector assignment/queue/actions app; reseller feed | Collector/collection feature and repository tests; physical collector workflow not tested |
| Skilled workers | Worker profile/skill-category models and directory | Web dashboard profile; app profile; public web/API directory | WorkerSkills, WebWorkerProfile and directory tests |
| Volunteers and verification | Volunteer approval; questionnaire; evidence-path storage; review service creates signed story and badge | Admin approval/review; volunteer app questionnaire/report; public verification display/story | Verification/story tests cover server flow; app report currently lacks evidence photo picking/upload and profile-photo upload |
| Donations/referrals/sales | UPI display-only settings, referral events/approval, sales report | Donation page; vendor/driver referral dashboard; sales PDF | Donation/referral/PDF tests; app commission interface and errand referral attribution remain unimplemented |
| Admin operations | District/locality, hubs, collector assignments, listing moderation, skill/transport categories, verification fee/review, posts, donation settings, data-request queue, password-change OTP | Admin web dashboard | Feature tests across admin modules; OTP protects password changes, but not each admin login |
| Data rights/security | encrypted PII, blind indexes, consent records, retention command, security headers, request throttles | Consent/export/deletion API; privacy/terms/disclaimer/contact pages | `DpdpTest` and security tests cover current behavior; export and deletion are incomplete as listed below |
| APK/runtime navigation | Flutter role app, secure token storage, Material UI, API client default, mobile navigation shell and safe-area wrapper | Android APK targets Shekuthi API/site; Home/Browse/Farm/Account/Back bottom navigation | 19 repository/token tests and analyzer pass; user reports past overlap; physical phone validation still required |

## Open gaps and production risks

1. **[Critical] Export file is stored on public disk:** `DataExportService`
   writes decrypted PII JSON to `Storage::disk('public')` and the controller
   returns its public URL. Filenames use sequential user IDs and timestamps,
   making other users' export files potentially guessable. Move exports to a
   private disk and provide an authenticated/expiring download route before
   public launch. (`backend/app/Services/DataExportService.php`,
   `DataExportController.php`)
2. **DPDP export completeness:** `DataExportService` currently exports user,
   vendor summary and consent rows only. Extend to the user's relevant bookings,
   errands, referrals/events, verifications, media references, device tokens and
   notifications. (`backend/app/Services/DataExportService.php`)
3. **DPDP deletion completeness:** `DataDeletionService` anonymizes user/vendor
   data but needs end-to-end review of encrypted guest contacts, media, device
   tokens, notifications, referrals and role-profile data. (`backend/app/Services/DataDeletionService.php`)
4. **Consent gaps:** errand contact capture and notification token opt-in need
   consent records; listing-image public consent is implemented. Check
   `ErrandController` and `DeviceTokenController`.
5. **Volunteer media clients:** verification evidence and volunteer profile
   photo upload exist server-side but are not fully reachable from the mobile
   app; add picker/upload/retry and test device permissions.
6. **Verification-story app link:** expose the story slug/reference in
   `ProductResource` and link from Flutter listing detail.
7. **Ordinary booking pickup/delivery request:** M28 farm-produce collections
   work through clients, but the separate booking-linked driver pickup/delivery
   request is still absent from web/app.
8. **Vendor app listing editing:** repository supports updates, but the Flutter
   listing edit route/form is not complete.
9. **Web notification inbox and app affiliate UI/errand attribution:** remain
   tracked parity gaps.
10. **Vendor delayed deletion:** requested flow to let vendors delete a listing
   after it has remained unpublished for a week has not been implemented.
11. **General web catalog filters:** `/catalog` has search/category, while API
    and dedicated sections support area/price filters.
12. **Operations:** verify Hostinger cron and schedule, backup/restore, media
    storage, SMTP deliverability, legal identity, FCM configuration and real
    admin/region data in production.
13. **Release hardening:** current APK uses the debug certificate and example
    application ID; create and securely back up the production signing key and
    choose the final application ID before Play Store release.
14. **Device/UI validation:** test on physical phones with gesture and
    three-button navigation, keyboard-open forms, image permissions and mobile
    widths; automated Flutter tests are mostly repository/model tests.

## Overall verdict

Core public browsing, booking, vendor dashboards, collectors, and approval
flows have code paths and automated coverage. **Not every planned function is
complete and the platform is not fully production-ready.** Close the DPDP,
volunteer evidence, story-link, booking-pickup and parity gaps above, and perform
authenticated Hostinger and physical-device tests before treating all functions
as operational.
