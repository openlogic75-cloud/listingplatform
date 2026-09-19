# Profile API (M2.1)

Bearer token required. Guests have no profile endpoints.

## GET /profile

`{ user: { ...auth profile, vendor?: {...}, worker?: {...} } }` - vendor rows
add display/description fields; workers add `services` and `service_areas`.

## PUT /profile

| Field | Applies to | Rules |
|---|---|---|
| name | all | sometimes, max 120 |
| phone | all | sometimes nullable, max 20; unique via blind index; encrypted |
| display_name | vendor | sometimes, max 120 |
| vendor_description | vendor | sometimes nullable, max 2000 |
| services | worker | sometimes nullable, max 2000 |
| service_areas | worker | sometimes nullable, max 500 |

`role` is intentionally not updatable (no self-escalation).

200 -> `{ user }`. Registration already created the role-specific row
(`vendors` / `worker_profiles` / `verification_volunteers` / `driver_availability`).
