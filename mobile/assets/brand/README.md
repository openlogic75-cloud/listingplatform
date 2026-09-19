# Brand assets for the Flutter app.

The logo lives in the repository root at `assets/brand/` (see its README).
Copy the files here before building:

    cp ../../assets/brand/logo.png      assets/brand/logo.png
    cp ../../assets/brand/logo-dark.png assets/brand/logo-dark.png   # optional

Only PNG is bundled: Flutter renders raster images out of the box, so the app
ships no SVG runtime. The website uses `logo.svg` instead.

`app_logo.dart` falls back to a text wordmark when these files are absent, so a
missing logo never breaks a build.