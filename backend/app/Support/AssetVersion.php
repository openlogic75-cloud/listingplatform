<?php

namespace App\Support;

/**
 * Versioned public-asset URLs (M15.5). The served stylesheets in
 * public/css/ are published from resources/css/ via `php artisan
 * assets:publish`; appending the file's mtime makes browsers fetch the new
 * file immediately instead of serving a stale cached copy.
 */
final class AssetVersion
{
    public static function url(string $path): string
    {
        $url = asset($path);
        $file = public_path($path);

        if (! is_file($file)) {
            return $url;
        }

        return $url.'?v='.filemtime($file);
    }
}
