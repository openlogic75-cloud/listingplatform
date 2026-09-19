# Backend - Laravel API + Website

One Laravel 11 application serving both the JSON API (`/api/v1/*`) for the
Flutter app and the public website (Blade). Target deployment: Hostinger
shared hosting (see `docs/decisions/Q7-hosting.md`).

## Requirements

- PHP >= 8.2 with pdo_mysql (pdo_sqlite suffices for tests)
- Composer 2.x
- MySQL 8 / MariaDB for local production-like runs (tests use in-memory SQLite)

## Setup

```bash
composer install
cp .env.example .env
php artisan key:generate            # also set PII_INDEX_KEY (see .env.example)
php artisan migrate --seed          # example districts/localities; replace per Q10
php artisan storage:link            # public media (product images, UPI QR)
php artisan serve                   # http://127.0.0.1:8000
```

## Layout map (owner tasks live in plan.md)

| Path | Purpose |
|---|---|
| `app/Http/Controllers/Api/` | JSON API for the Flutter app (auth, later modules) |
| `app/Http/Controllers/Web/` | Website pages (home, about, catalog, donation) |
| `app/Models/` | Eloquent models; PII columns use `encrypted` casts |
| `app/Support/BlindIndex.php` | Keyed HMAC hashes that make encrypted PII searchable |
| `database/migrations/` | Full schema (users, vendors, districts, localities, rider bases, products, bookings, logistics, errands, verification, donations, referrals, DPDP, media) |
| `resources/css/tokens.css` | Website design tokens (minimalist-swiss) |
| `resources/css/admin-tokens.css` | Dashboard tokens (genesis) for the admin UI landing in M4.1/M6.2 |
| `public/icons/` | Copied subset of `assets/icons/` (outline SVG, `currentColor`) |
| `public/img/` | Copied illustrations from `assets/illustrations art/` |

## API surface (phase 1)

- `POST /api/v1/auth/register` - vendors, drivers, collectors, workers, volunteers only; buyers never register
- `POST /api/v1/auth/login` - email + password, returns Sanctum token
- `GET  /api/v1/auth/me` - profile (Bearer token)
- `POST /api/v1/auth/logout` - revoke current token

## Website pages (phase 1)

`/` home, `/about` mission, `/catalog` browse (empty state ready), `/donation` UPI display (renders admin settings when present).

## Tests

```bash
php artisan test
```

## Security notes

- Never commit `.env`; `PII_INDEX_KEY` and `APP_KEY` are generated per environment.
- Email/phone are encrypted at rest; only keyed blind-index hashes are queryable.
- Registration and login endpoints are rate-limited.
