# Local Goods Marketplace

Open-source platform connecting buyers, sellers/vendors (farmers, traditional and agro products, rentals/homestays), logistics (drivers/riders, collectors), skilled workers and verification volunteers. The platform handles no transactions and charges no commission - it runs on donations (UPI).

## Repository layout

| Path | What it is |
|---|---|
| `backend/` | Laravel 11 (PHP 8.2+) - JSON API for the app + public website (Blade). One folder for both, as deployed on Hostinger. |
| `mobile/` | Flutter (Dart) - single role-based Android app (buyer guest, vendor, driver, collector, skilled worker, volunteer). |
| `docs/` | Decisions (one ADR per open question), design tokens, deploy notes. |
| `plan.md` | Status tracker - every task has status, comment and owning file paths. Update it with every change. |
| `AGENTS.md` | Process rules for coding agents (stack-agnostic). |
| `assets/`, `ui deisgns/`, `UX/`, `local-market.md`, `additionalfeatures.txt` | Read-only reference material. Never edit; copy out what a task needs. |

## Quick start - backend

Requires PHP >= 8.2, Composer, MySQL (SQLite works for tests out of the box).

```bash
cd backend
composer install
cp .env.example .env
php artisan key:generate
php artisan migrate --seed        # seeds example districts/localities (replace per Q10 before production)
php artisan serve                 # http://127.0.0.1:8000
```

API base: `http://127.0.0.1:8000/api/v1`. Website: `http://127.0.0.1:8000/`.

## Quick start - mobile

Requires the Flutter SDK (stable channel).

```bash
cd mobile
flutter create . --org com.listingplatform --project-name listingplatform   # generates android/ platform folder once
flutter pub get
flutter run --dart-define=API_BASE_URL=http://10.0.2.2:8000/api/v1          # Android emulator reaches host via 10.0.2.2
```

## Hard rules for all contributions

1. Update `plan.md` with every change (status, dated note, files touched, change-log row).
2. No emojis anywhere in the built project UI or code - icons come from the SVG icon library (`assets/icons/`, copied into `backend/public/icons/` and `mobile/assets/icons/`).
3. Personal data (name, phone, email, address) is encrypted at rest; lookups go through blind-index columns. Keys live only in `.env`.
4. Buyers never register - they browse as guests. Only vendors, drivers, collectors, skilled workers and volunteers register.
