#!/usr/bin/env python3
"""API contract check: every path the Flutter app calls must exist in Laravel.

Flutter's SDK is not required for this. The app's repositories name their
endpoints as string literals; the backend names its routes in routes/api.php.
Comparing the two catches drift (a renamed route, a typo'd path) before an
emulator ever starts.

Usage (from the repository root):

    python3 mobile/tool/check_api_contracts.py

Exit code 0 = every app path matched a route.
"""
from __future__ import annotations

import glob
import re
import sys

ROUTES = "backend/routes/api.php"
APP_FILES = "mobile/lib/**/*.dart"
API_CLIENT = "mobile/lib/core/network/api_client.dart"

HTTP_VERBS = ("get", "post", "put", "patch", "delete")

# `_api.dio.get<Map<String, dynamic>>(` - the arguments may start on the next
# line, so the window below is scanned rather than a single line.
CALL = re.compile(
    r"\.(get|post|put|patch|delete)\s*(?:<[^>]*>)?\s*\(",
)
# A path literal, optionally with leading/trailing interpolation.
PATH_LITERAL = re.compile(r"'([^'\n]*)" + r"([^'\n]*)'")

# Route::get('/catalog/{product}')
ROUTE = re.compile(
    r"Route::(get|post|put|patch|delete)\(\s*'([^']+)'",
    re.MULTILINE,
)


def api_prefix() -> str:
    """Path part of the app's base URL, e.g. `/api/v1`."""
    with open(API_CLIENT, encoding="utf-8") as handle:
        source = handle.read()

    match = re.search(r"defaultValue:\s*'([^']+)'", source)
    if match is None:
        return "/api/v1"

    url = match.group(1)
    without_scheme = re.sub(r"^[a-z]+://", "", url)
    slash = without_scheme.find("/")
    if slash == -1:
        return "/api/v1"
    return "/" + without_scheme[slash:].strip("/")


def normalise_dart_path(raw: str) -> str:
    """`/listings/$id/status` -> `/listings/*/status`."""
    path = raw
    path = re.sub(r"\$\{[^}]*\}", "*", path)
    path = re.sub(r"\$[A-Za-z_][A-Za-z0-9_]*", "*", path)
    path = path.split("?")[0].rstrip("/")
    return "/" + path.strip("/")


def app_paths() -> dict[str, list[str]]:
    found: dict[str, list[str]] = {}
    for file in sorted(glob.glob(APP_FILES, recursive=True)):
        with open(file, encoding="utf-8") as handle:
            source = handle.read()

        for match in CALL.finditer(source):
            # Window covers the argument list of this call; the first path
            # literal in it is the endpoint.
            window = source[match.end():match.end() + 400]
            window = window.split(";")[0]

            literal = PATH_LITERAL.search(window)
            if literal is None:
                continue
            candidate = literal.group(1) + literal.group(2)
            if not candidate.startswith("/"):
                continue
            path = normalise_dart_path(candidate)
            found.setdefault(path, []).append(file)
    return found


def route_paths() -> set[str]:
    with open(ROUTES, encoding="utf-8") as handle:
        source = handle.read()

    paths: set[str] = set()
    for match in ROUTE.finditer(source):
        path = match.group(2)
        path = "/" + path.strip("/")
        # Laravel {param} -> wildcard, matching the Dart normaliser.
        path = re.sub(r"\{[^}]+\}", "*", path)
        # routes/api.php prefixes everything with v1 (Route::prefix('v1')).
        paths.add("/api/v1" + ("" if path == "/" else path))
    return paths


def matches(app_path: str, routes: set[str]) -> bool:
    if app_path in routes:
        return True
    app_segments = app_path.strip("/").split("/")
    for route in routes:
        route_segments = route.strip("/").split("/")
        if len(route_segments) != len(app_segments):
            continue
        if all(
            r == "*" or a == "*" or r == a
            for r, a in zip(route_segments, app_segments)
        ):
            return True
    return False


def main() -> int:
    prefix = api_prefix()
    routes = route_paths()
    app = {prefix + path if not path.startswith(prefix) else path: files
           for path, files in app_paths().items()}

    missing = {p: f for p, f in app.items() if not matches(p, routes)}

    for path, files in sorted(missing.items()):
        print(f"MISSING ROUTE {path}  <- {', '.join(sorted(set(files)))}")

    print(
        f"prefix: {prefix} | app paths: {len(app)} | backend routes: {len(routes)}"
        f" | unmatched: {len(missing)}"
    )
    return 1 if missing else 0


if __name__ == "__main__":
    sys.exit(main())