# Q7 - Hosting limits (Hostinger shared hosting)

- Status: open, partially decided (2026-09-05)
- Task: M1.1, M7.5, M8.1, M8.4

## Decided constraints (design around these)

- Shared hosting: **no Redis, no long-lived queue workers**.
  - Cache store: `database`.
  - Queue connection: `database`, but jobs run synchronously in requests until cron is confirmed.
  - Sessions: `database`.
- Media on the `public` local disk first (S3-compatible object storage later, Q9).
- PHP: target 8.2+ (this repo is authored against Laravel 12, which requires PHP >= 8.2).
- Secrets only in `.env`; never committed.

## Still to confirm with the host

- Exact PHP version on the target plan (affects `composer.json` platform pin).
- Cron job availability (decides M7.5 retention sweeps and M8.1 notification dispatch).
- Whether the plan allows `storage/` symlink (otherwise point the web server docroot at `backend/public` or use X-Accel-style routing via `.htaccess`).

## Consequences

- `backend/.env.example` ships with database-driven cache/queue/session defaults.
- Deployment notes stay in `docs/deploy/hostinger.md` (M8.4).
