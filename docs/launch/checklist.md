# Launch checklist (M8.5)

> Everything a human operator must verify before the first public launch.
> Check each box only when the item is actually true in the deployed
> environment.

## 1. Content & region

- [ ] **First region seeded (Q10).** `DistrictLocalitySeeder` contains the
      real first-region district(s) and locality names — not the example
      `Example District One/Two` seed. Run with `--force`.
- [ ] **First admin account exists.** Created manually via
      `php artisan tinker` or a one-off seeder; password rotated after the
      first login.
- [ ] **Privacy policy is live at `/privacy`.** Copy reviewed against DPDP
      (consent text, data export, deletion, retention). Consent text version
      matches `BookingService::CONSENT_TEXT_VERSION = '1.0'`.

## 2. Money boundary

- [ ] **Donation page verified end-to-end.** `/donation` renders the UPI ID
      and QR from `donation_settings`; the QR deep link opens a UPI app on a
      real Android device; platform stores no payment data.
- [ ] **No payment credentials anywhere** in code, logs, or `.env`.

## 3. Verification & trust

- [ ] **A site visit was completed in staging on a real vendor** — volunteer
      profile → report → evidence upload → admin approve → badge visible on
      the listing and vendor page with the volunteer's name.

## 4. Logistics & errands

- [ ] **Rider base + online toggle smoke test.** A driver set a district +
      ≤5 localities, went online, and appeared in the matching pool for a
      pickup job in one of their localities.
- [ ] **Guest errand flow smoke test.** Created an errand as a guest with a
      phone, tracked it by code + phone, and completed it as the assigned
      driver.

## 5. Data rights (DPDP)

- [ ] **Data export returns a file.** `/api/v1/export` as a real user
      produces a downloadable JSON containing their decrypted profile and
      consents.
- [ ] **Deletion anonymizes.** `/api/v1/deletion` replaces the profile with
      `Deleted User` and the action is recorded in `data_requests`; badge
      volunteer-name snapshots survive deletion.

## 6. Deployment health

- [ ] **Migrations + caches applied** (`migrate --force`, `config:cache`,
      `route:cache`) and the site serves 200 on `/`, `/about`, `/catalog`,
      `/donation`.
- [ ] **HTTPS enforced** (Let's Encrypt via hPanel) and all links use the
      https `APP_URL`.
- [ ] **Backups scheduled** — DB snapshot + `storage/app/public` media dump
      to a location outside the docroot.
- [ ] **Monitoring** — error log level is `warning`; someone checks
      `storage/logs/laravel.log` weekly for the first month.

## 7. Brand & store listing (M0.7, Q13)

- [ ] Brand name, logo, and palette decided and applied (logo in
      `public/img/`, tokens in `public/css/tokens.css`, favicon SVG).
- [ ] Play Store listing assets prepared from the assets library
      (`assets/illustrations art/` for screenshots) — no fabricated metrics.

## 8. Analytics stance

- [ ] **Analytics decision recorded.** Default is none — privacy-first.
      If any analytics are added later, note the tool + scope in
      `docs/decisions/` before enabling.

## 9. The day-of go-live runbook order

1. Flush expired sessions/caches.
2. Confirm `APP_DEBUG=false`.
3. Walk `/about` (mission), `/catalog` (a full listing), `/donation` (UPI),
   and `/privacy` in an incognito window.
4. Create one real guest booking on the website; confirm it appears in the
   vendor's incoming list.
5. Open the Android app, log in as the test admin, and confirm push/in-app
   notification arrives for the step 4 booking status change.