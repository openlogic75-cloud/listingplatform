# Listings API (M2.2) - vendor CRUD

Bearer token required. Ownership enforced by `ProductPolicy` (owner vendor or admin).

## GET /listings -> `{ data: [Product] }` (all own statuses)

## POST /listings (rate limit 30/min)

| Field | Rules |
|---|---|
| title | required, max 120 |
| category | required: `traditional / agro / rental_homestay` |
| description | optional, max 5000 |
| price | optional numeric >= 0; **required for rentals** |
| unit | optional, max 20 (kg, jar, night, ...) |
| moq | optional int >= 1 (default 1); **prohibited for rentals** |
| stock | optional int >= 0; **prohibited for rentals** |
| available_from / available_to | dates; **required for rentals**; to >= from |
| images | optional array (max 8) of media paths you uploaded (validated) |
| district_id / locality_id | optional; locality must belong to district |
| status | `draft` (default) / `active` / `inactive` / `archived` |

201 -> `{ data: Product }`. 403 for non-vendors. 422 validation errors.

## PUT /listings/{product}

Partial update (same rules, all optional; category-dependent pairs still enforced). 200 -> `{ data: Product }` | 403 other vendor's listing.

## DELETE /listings/{product}

Soft-off: archives the listing (status=archived), keeps the row. 200 -> `{ message }`.
