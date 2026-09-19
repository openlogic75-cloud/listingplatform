# Auth API (M1.4)

Base: `/api/v1`. All requests send `Accept: application/json`. Tokens: Sanctum bearer.

## POST /auth/register

Rate limit: 10/min. Only these roles may register - buyers never register (guests):

`vendor | driver | collector | worker | volunteer`

| Field | Rules |
|---|---|
| name | required, max 120 |
| email | required, email, unique (case-insensitive via blind index) |
| phone | optional, max 20, unique (encrypted at rest) |
| password | required, confirmed, min 8 |
| role | required, one of the registerable roles |
| district_id | optional, must exist |
| display_name | required if role=vendor |
| vendor_category | required if role=vendor; `traditional/agro/rental_homestay` |

Side effects per role: vendor -> `vendors` row; driver/collector -> `driver_availability` (offline); volunteer -> `verification_volunteers`; worker -> `worker_profiles`.

Response 201: `{ "token": "1|...", "user": { id, name, email, phone, role, district_id, is_active, created_at } }`

## POST /auth/login

Rate limit: 6/min. `{ email, password, device_name? }` -> 200 `{ token, user }` | 422 invalid credentials | 403 deactivated account.

## GET /auth/me (Bearer) -> `{ user }`

## POST /auth/logout (Bearer) -> revokes the current token.

## PII notes

- name/email/phone are encrypted at rest (`encrypted` casts).
- `email_index`/`phone_index` are keyed HMAC hashes for lookup; they never serialize.
- Passwords: bcrypt; profile update ignores `role` (no self-escalation).
