<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

/**
 * Publish source stylesheets to the served copies (M15.5).
 *
 * This project has no node build: `resources/css/` is the source of truth
 * and `public/css/` holds the copies the web server actually serves. Every
 * CSS change is followed by this command, locally and on deploy. Forgetting
 * it means the browser keeps rendering the old stylesheet even though the
 * rendered markup changed.
 */
class PublishAssets extends Command
{
    protected $signature = 'assets:publish';

    protected $description = 'Copy resources/css into public/css (the served stylesheets).';

    public function handle(): int
    {
        $source = resource_path('css');
        $target = public_path('css');

        $files = glob($source.'/*.css');

        if ($files === false || $files === []) {
            $this->error('No stylesheets found in resources/css.');

            return self::FAILURE;
        }

        if (! is_dir($target) && ! mkdir($target, 0755, true) && ! is_dir($target)) {
            $this->error("Could not create {$target}.");

            return self::FAILURE;
        }

        $published = 0;

        foreach ($files as $file) {
            if (copy($file, $target.'/'.basename($file))) {
                $published++;
            }
        }

        $this->info("Published {$published} stylesheet(s) to public/css.");

        return self::SUCCESS;
    }
}
