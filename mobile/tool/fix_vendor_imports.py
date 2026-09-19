#!/usr/bin/env python3
"""One-off repair of relative imports under lib/features/vendor/.

The vendor screens were written one directory deeper than the other feature
folders, so `../../core/...` and `../catalog/...` pointed outside the tree.
Rewrites them to the correct depth. Idempotent.
"""
import io

FIXES = {
    "lib/features/vendor/bookings/vendor_bookings_repository.dart": [
        ("../../core/network/api_client.dart", "../../../core/network/api_client.dart"),
    ],
    "lib/features/vendor/bookings/vendor_bookings_screen.dart": [
        ("../../core/network/api_client.dart", "../../../core/network/api_client.dart"),
    ],
    "lib/features/vendor/listings/listings_controller.dart": [
        ("../../core/network/api_client.dart", "../../../core/network/api_client.dart"),
        ("../catalog/catalog_repository.dart", "../../catalog/catalog_repository.dart"),
    ],
    "lib/features/vendor/listings/listings_repository.dart": [
        ("../../core/network/api_client.dart", "../../../core/network/api_client.dart"),
        ("../catalog/catalog_repository.dart", "../../catalog/catalog_repository.dart"),
    ],
    "lib/features/vendor/listings/listings_screen.dart": [
        ("../catalog/catalog_repository.dart", "../../catalog/catalog_repository.dart"),
    ],
    "lib/features/vendor/referrals/referrals_repository.dart": [
        ("../../core/network/api_client.dart", "../../../core/network/api_client.dart"),
    ],
    "lib/features/vendor/referrals/referrals_screen.dart": [
        ("../../core/network/api_client.dart", "../../../core/network/api_client.dart"),
        ("../../core/theme/tokens.dart", "../../../core/theme/tokens.dart"),
    ],
}

for path, replacements in FIXES.items():
    body = io.open(path, encoding="utf-8").read()
    original = body
    for old, new in replacements:
        body = body.replace(f"'{old}'", f"'{new}'")
    if body != original:
        io.open(path, "w", encoding="utf-8").write(body)
        print("fixed", path)
    else:
        print("no change", path)