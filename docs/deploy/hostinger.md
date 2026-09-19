# Shekuthi Hostinger shared-hosting deployment (M8.4/M32.2)

> Status: reference — apply once the target plan, PHP version, and cron access
> are confirmed with the host (Q7). Everything here matches the codebase's
> Hostinger constraints: **no Redis, no long-lived queue workers**, media on
> the `public` disk, database cache/session/queue drivers.

## Readiness verdict

The Laravel website/API is **compatible with Hostinger shared hosting** when
the plan provides PHP 8.2+ and the required extensions below. It is **not ready
for public go-live yet** until DNS/SSL for `shekuthi.in`, database, legal
identity, mail, first admin/region, backups and cron are verified. See
`docs/launch/checklist.md`.

The Flutter app is a separate release: build it with
`--dart-define=API_BASE_URL=https://shekuthi.in/api/v1` and
`--dart-define=SITE_BASE_URL=https://shekuthi.in`. Android release signing is
still separate from this PHP deployment and is not complete in the repository.

## 1. Confirm the plan (Q7)

- Exact PHP version (target >= 8.2; Laravel 12). Used to pin `composer.json`'s
  `platform` so `composer install --no-dev` resolves reliably.
- Required PHP extensions: `pdo_mysql`, `mbstring`, `openssl`, `ctype`,
  `fileinfo`, `gd` (2 MB image resize/WebP), `dom` (PDF reports), `tokenizer`
  and `xml`.
- Cron job availability. Decides whether M7.5 retention sweeps and M8.1
  notification cleanup run on a schedule or as a documented manual command.
- Web-server layout. Prefer pointing the docroot at `backend/public/`; if
  that is not allowed, use a symlink (`storage` link) or the `.htaccess`
  rewrite that routes to `backend/public/index.php`.

## 2. Layout on the host

```
~/domains/<app>/
├── backend/            # whole Laravel app + blade website (upload as-is)
└── public_html/        # docroot for https://shekuthi.in
    └── index.php       # the backend/public/index.php file
```

Upload `backend/` excluding `node_modules/`, `.git/`, `tests/`, and
`storage/logs/*.log` (or exclude via `.gitattributes` export-ignore).

The safest layout points the domain document root directly at `backend/public/`.
If hPanel requires `public_html/`, copy the contents of `backend/public/` there
and keep the Laravel application directory outside the document root; update
`public_html/index.php` paths accordingly.

## 3. Environment

Copy `backend/.env.production.example` to `backend/.env` and fill in:

| Key | Value |
|-----|-------|
| `APP_ENV` | `production` |
| `APP_KEY` | `php artisan key:generate --show` output |
| `APP_URL` | `https://shekuthi.in` |
| `DB_*` | panel-created database (see below) |
| `PII_INDEX_KEY` | `php -r "echo bin2hex(random_bytes(32));"` |
| `FCM_SERVER_KEY` | legacy FCM server key (optional; empty = in-app inbox only) |
| `CACHE_STORE` | `database` |
| `SESSION_DRIVER` | `database` |
| `QUEUE_CONNECTION` | `database` |
| `SANCTUM_STATEFUL_DOMAINS` | `shekuthi.in,www.shekuthi.in` |
| `APP_CRON_ENABLED` | `false` until dry-run and cron verification; then `true` |

Before caching configuration, replace every `<...>` placeholder in
`.env.production.example`, especially `APP_KEY`, `PII_INDEX_KEY`, database
credentials, legal identity and SMTP credentials. Never use the example file
as the live `.env` without filling it.

## 4. Database via panel

- Create the database + user in hPanel.
- Run migrations from `backend/`:
  `php artisan migrate --force`
- Seed the first region (after Q10 decides the district list):
  `php artisan db:seed --class=DistrictLocalitySeeder --force`
  (replace the example districts with the real first-region data first.)
- Install optimized dependencies:
  `composer install --no-dev --prefer-dist --optimize-autoloader`
- Verify the host before going live:
  `composer check-platform-reqs --no-dev`

## 5. Storage + permissions

```bash
cd backend
php artisan storage:link          # if the docroot layout allows it
chmod -R ug+rw storage bootstrap/cache
```

If `storage:link` is unavailable, drop a `storage/` symlink inside the
docroot to `../backend/storage/app/public` and confirm asset URLs.

## 6. Cron (required for retention)

If cron is available, register one line (localhost connection):

```
* * * * * cd /home/<user>/domains/<app>/backend && php artisan schedule:run >> /dev/null 2>&1
```

After a successful dry run, set `APP_CRON_ENABLED=true` and confirm the
schedule lists the daily `retention:sweep` command. Keep the cron disabled
until that verification is complete.

If cron is NOT available: run the retention sweep manually with
`php artisan retention:sweep --dry-run` first, then the live command during a
controlled maintenance window; document the manual cadence in the launch
runbook.

## 6b. Publish stylesheets

`resources/css/` is the source of truth; the web server serves the copies in
`public/css/`. After any CSS change, publish them:

```
php artisan assets:publish
```

Skipping this means the browser keeps rendering the old stylesheet even
though the markup changed (this bit us once — M15.5). Asset URLs carry a
mtime version, so no manual cache clearing is needed once published.

## 7. SSL + cache

- Enable free SSL in hPanel (Let's Encrypt) and force HTTPS in panel config.
- Clear caches after deploy:
  `php artisan config:cache && php artisan route:cache && php artisan view:cache`
- `php artisan optimize` for OPcache-aware production.

Smoke-test `https://shekuthi.in/`, `/about`, `/catalog`, `/donation`, `/privacy`,
`/terms` and `/api/v1/locations` after the cache step.

## 8. Update flow

1. Put the site in maintenance mode if the change includes migrations.
2. Upload changed files over the old `backend/` tree.
3. `php artisan migrate --force` if there are new migrations.
4. `php artisan assets:publish` if any CSS changed.
5. Re-run `php artisan config:cache && php artisan route:cache && php artisan view:cache`.
6. Confirm the smoke-test URLs and exit maintenance mode.

## 9. Media backup

The storage `public/` directory holds products, UPI QR, and evidence.
Schedule a weekly download of `backend/storage/app/public/` (panel backup or
a cron `tar` to a private directory outside the docroot). Keep the media
backup separate from DB backups.
