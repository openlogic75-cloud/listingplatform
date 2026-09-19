# Brand assets — drop-in location

Put the project logo here. Both clients read from these filenames, so replacing
the logo never requires a code change.

| File | Used by | Notes |
|---|---|---|
| `logo.png` | Flutter app (light theme) | Transparent background, at least 512 px wide |
| `logo-dark.png` | Flutter app (dark theme) | Optional. Falls back to `logo.png` |
| `logo.svg` | Website header | Optional. Preferred for the site if present |
| `favicon.png` | Website tab icon | 64x64 or larger |

## How to replace

1. Drop the files in this folder (keep the exact filenames above).
2. Copy them for the website too:

   ```
   cp assets/brand/logo.svg   backend/public/img/logo.svg
   cp assets/brand/favicon.png backend/public/img/favicon.png
   ```

3. Nothing else. The app resolves the logo from `mobile/assets/brand/`, the
   website from `backend/public/img/` (both wired in M0.7).

## Until a logo exists

Both clients fall back to a plain text wordmark, so the build is never broken
by a missing image. The brand name itself lives in exactly two places:
`backend/config/branding.php` (website) and
`mobile/lib/core/widgets/app_logo.dart` (app wordmark fallback).

Do not commit generated platform icons here — those belong in
`mobile/android/app/src/main/res/` and are produced by `flutter_launcher_icons`.