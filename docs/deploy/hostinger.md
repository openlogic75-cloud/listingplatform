# Hostinger shared-hosting deployment (M8.4)

> Status: reference — apply once the target plan, PHP version, and cron access
> are confirmed with the host (Q7). Everything here matches the codebase's
> Hostinger constraints: **no Redis, no long-lived queue workers**, media on
> the `public` disk, database cache/session/queue drivers.

## 1. Confirm the plan (Q7)

- Exact PHP version (target >= 8.2). Used to pin `composer.json`'s
  `platform` so `composer install --no-dev` resolves reliably.
- Cron job availability. Decides whether M7.5 retention sweeps and M8.1
  notification cleanup run on a schedule or as a documented manual command.
- Web-server layout. Prefer pointing the docroot at `backend/public/`; if
  that is not allowed, use a symlink (`storage` link) or the `.htaccess`
  rewrite that routes to `backend/public/index.php`.

## 2. Layout on the host

```
~/domains/<app>/
├── backend/            # whole Laravel app + blade website (upload as-is)
└── public_html/        # docroot
    └── index.php       # the backend/public/index.php file
```

Upload `backend/` excluding `node_modules/`, `.git/`, `tests/`, and
`storage/logs/*.log` (or exclude via `.gitattributes` export-ignore).

## 3. Environment

Copy `backend/.env.example` to `backend/.env` and fill in:

| Key | Value |
|-----|-------|
| `APP_ENV` | `production` |
| `APP_KEY` | `php artisan key:generate --show` output |
| `APP_URL` | `https://<your-app-domain>` |
| `DB_*` | panel-created database (see below) |
| `PII_INDEX_KEY` | `php -r "echo bin2hex(random_bytes(32));"` |
| `FCM_SERVER_KEY` | legacy FCM server key (optional; empty = in-app inbox only) |
| `CACHE_STORE` | `database` |
| `SESSION_DRIVER` | `database` |
| `QUEUE_CONNECTION` | `database` |

## 4. Database via panel

- Create the database + user in hPanel.
- Run migrations from `backend/`:
  `php artisan migrate --force`
- Seed the first region (after Q10 decides the district list):
  `php artisan db:seed --class=DistrictLocalitySeeder --force`
  (replace the example districts with the real first-region data first.)

## 5. Storage + permissions

```bash
cd backend
php artisan storage:link          # if the docroot layout allows it
chmod -R ug+rw storage bootstrap/cache
```

If `storage:link` is unavailable, drop a `storage/` symlink inside the
docroot to `../backend/storage/app/public` and confirm asset URLs.

## 6. Cron (if the plan allows)

If cron is available, register one line (localhost connection):

```
* * * * * cd /home/<user>/domains/<app>/backend && php artisan schedule:run >> /dev/null 2>&1
```

The schedule (M7.5) currently holds no enabled jobs until retention jobs are
built; add them to `routes/console.php` when they land.

If cron is NOT available: run the retention sweep manually with
`php artisan dpdp:sweep` and document it in the launch runbook.

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
  `php artisan config:cache && php artisan route:cache`
- `php artisan optimize` for OPcache-aware production.

## 8. Update flow

1. Upload changed files over the old `backend/` tree.
2. `php artisan migrate --force` if there are new migrations.
3. `php artisan assets:publish` if any CSS changed.
4. Re-run `php artisan config:cache && php artisan route:cache`.

## 9. Media backup

The storage `public/` directory holds products, UPI QR, and evidence.
Schedule a weekly download of `backend/storage/app/public/` (panel backup or
a cron `tar` to a private directory outside the docroot). Keep the media
backup separate from DB backups.