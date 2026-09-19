# Security hardening checklist (M7.4)

> Living verification doc — every item below maps to a concrete place in
> `backend/`. Run through it before production and after any change that
> touches routes, validation, or uploads.

## Input validation
- [x] **Every endpoint validates.** Router-level: FormRequest classes or
      inline `$request->validate()`; no controller reads request data without
      rules. Audit: `grep -rn "request->(input|all)\|Request::" app/Http/Controllers` — each hit must sit next to a `validate(`. `— 2026-09-18: re-audited with M5.4/M4.6 added; all 6 raw-read hits sit beside validate() rules (ListingController status via ListingRequest in:..., DriverBaseController district inside closure rule, MediaController directory in:..., VerificationController roles/collection-all, CatalogController validate).`
- [x] **Uploads allow-listed** (`app/Support/UploadValidator.php`): content-detected MIME (JPEG/PNG/WebP only), 5 MB cap, 4096 px cap, GD re-encode to strip payloads, uploader-scoped path references for listings/evidence. `— 2026-09-18: unchanged, still enforced via MediaController + validatedImages().`

## Authentication & authorization
- [x] **bcrypt/argon2** — `User` model uses the `hashed` cast; password policy via `Password::min(8)`.
- [x] **Sanctum tokens** for all app API calls; `auth:sanctum` middleware on the group; throttled `login` (6/min) and `register` (10/min).
- [x] **Role gates** enforced at every sensitive action (vendor listing policy, driver-only base/availability, volunteer-only reports, admin-only approve/reject/verification list). No trust-the-client role checks.

## Session & CSRF
- [x] **CSRF on web forms** (`Route::post('/listings/{product}/book')`, admin PUT/POST forms, referral attribute) via Laravel's default `web` middleware — verified by the 419 on curl without a session token.
- [x] **CORS pinned** — API states `SANCTUM_STATEFUL_DOMAINS`; `allowed_origins: ["*"]` with `supports_credentials: false` (token clients only, no cookies). Discretionary new origins require an allow-list entry, never `*` with credentials. `— 2026-09-18: verified via tinker config('cors')`.

## Data protection
- [x] **PII encrypted at rest** (`email`, `phone`, booking/errand contact) with Laravel `encrypted` casts; lookup only via keyed blind-index columns. `— 2026-09-18: confirmed casts on User, Booking, Errand models.`
- [x] **No secrets in code.** `.env` only; `.gitignore` blocks `.env`; `PII_INDEX_KEY` and `FCM_SERVER_KEY` empty in any committed example.
- [ ] **DATA_EXPORT works and DATA_DELETION anonymizes** (see `docs/launch/checklist.md` §5) — verified before launch. `— 2026-09-18: functional tests green (DpdpTest); end-to-end export file + full deletion cascade re-verified at launch.`

## Headers & transport
- [ ] **HTTPS enforced** on the host (Let's Encrypt) — behind a panel proxy, Laravel `trustedProxies` is configured so HTTPS detection is correct. `— deploy-time item (docs/deploy/hostinger.md).`
- [x] **Security headers** via `app/Http/Middleware/SecurityHeaders.php` on every response (X-Content-Type-Options, X-Frame-Options DENY, X-XSS-Protection 0, Referrer-Policy, Permissions-Policy, COOP same-origin); added 2026-09-18: `Strict-Transport-Security` (HSTS fallback) + strict CSP (`default-src 'self'; script-src 'self'; style-src 'self' 'unsafe-inline'` for Blade style attrs; no inline/external JS in the templates).

## Rate limiting
- [x] Covered: `auth/register` (10/min), `auth/login` (6/min), `bookings` (10/min), `bookings/lookup` (10/min), `errands` store/lookup (10/min), `media` (30/min), listing create (30/min), referral create (30/min), export/deletion (10/min), device tokens (30/min).
  `— 2026-09-18: added throttle:10,1 to web booking.store and referral.attribute (previously unthrottled web POST endpoints).`

## Admin
- [x] **Admin paths** (`/admin/*`) are reachable only by role-gated handlers; no public route lists them. `— 2026-09-18: verified admin login throttled 5/min; all dashboard routes behind 'admin' middleware (EnsureUserIsAdmin).`
- [ ] **Optional admin MFA** recorded as a follow-up; not shipped by default (documented decision).

## Audit trail
- [x] `data_requests` rows recorded for every export/deletion with outcome.
- [ ] Sensitive admin actions (approve/reject verification, donation settings change) appear in app logs / task note history.