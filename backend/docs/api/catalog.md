# Catalog API (M2.4) - guest browsing

Public endpoints, no token. Buyers browse without an account.

## GET /catalog

Query params (all optional):

| Param | Rules |
|---|---|
| q | max 120; matches title/description only (PII is never searchable) |
| category | `traditional / agro / rental_homestay` |
| district_id / locality_id | integer |
| min_price / max_price | numeric; max >= min |
| page / per_page | page >= 1; per_page 1..50 (default 24) |

Response 200: `{ data: [Product], meta: { current_page, last_page, total } }`.

Product fields: `id, title, category, description, price, unit, moq, stock, available_from, available_to, images[], district_id, locality_id, status, is_verified, vendor{id, display_name, category}, created_at`.

Only `active` listings are returned; drafts/archived are never visible publicly.

## GET /catalog/{product}

200 `{ data: Product }` | 404 when not active.

## GET /vendors/{vendor}

Public vendor page: `{ data: { id, display_name, category, description, district_id, is_verified, listings: [Product] } }`. Personal contact data is never exposed.
