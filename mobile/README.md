# Mobile - Flutter App (Android)

Single role-based Flutter app: buyers browse as guests; vendors, drivers,
collectors, workers and volunteers register. Talks to the Laravel API in
`../backend` at `/api/v1`.

## One-time platform setup

The Dart code is committed without platform folders. On a machine with the
Flutter SDK (stable channel):

```bash
flutter create . --org com.listingplatform --project-name listingplatform
flutter pub get
```

This generates `android/` (and other platform folders) with Flutter default
SDK versions - minSdk 21, per `docs/decisions/Q14-android-sdk.md`.

## Run

```bash
# Backend must be reachable: start it with `php artisan serve` in ../backend.
# 10.0.2.2 is the Android emulator alias for the host machine.
flutter run --dart-define=API_BASE_URL=http://10.0.2.2:8000/api/v1
```

## Test and analyze

```bash
flutter analyze
flutter test
```

## Layout map (owner tasks live in plan.md)

| Path | Purpose |
|---|---|
| `lib/core/theme/` | Material 3 themes + design tokens (brand seed, spacing, radius) |
| `lib/core/network/api_client.dart` | Dio client with bearer-token interceptor |
| `lib/core/storage/token_storage.dart` | Sanctum token in flutter_secure_storage (Keystore-backed) |
| `lib/core/router/app_router.dart` | go_router configuration |
| `lib/features/auth/` | Register (role selection) and login, session controller |
| `lib/features/home/` | Home shell with milestone placeholders |
| `assets/icons/` | Copied subset of `assets/icons/` (Tabler-style SVG, outline default) |

## Hard rules

- No emojis anywhere in UI. Icons: Material icon font for platform chrome;
  Tabler SVG assets for domain icons, tinted via the theme.
- All interactive elements at or above 48dp.
- Labels above inputs; errors below in error color; no floating placeholders.
