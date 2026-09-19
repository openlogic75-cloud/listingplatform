# Media API (M2.3) - shared image upload

Bearer token required. One validator (`App\Support\UploadValidator`) serves
every upload type: product images now, UPI QR (M6.2) and evidence (M5.2) later.

## POST /media (rate limit 30/min)

multipart/form-data:

| Field | Rules |
|---|---|
| file | required image; content-verified (not client header) |
| directory | optional: `products` (default) / `avatars` |

Server-side checks: MIME allow-list (JPEG/PNG/WebP by content), max 5 MB, max 4096px, GD re-encode when available (strips embedded payloads).

201 -> `{ id, path, mime, url }`

## Image references on listings

`POST /listings` / `PUT /listings/{product}` accept `images: [path, ...]`.
Every path is validated against `media` rows uploaded by the requesting user -
other users' media and arbitrary paths are dropped.
