<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * M15.5: the served stylesheets in public/css are published from
 * resources/css, and asset URLs carry a version so browsers never keep
 * rendering a stale stylesheet.
 */
class AssetPublishTest extends TestCase
{
    use RefreshDatabase;

    public function test_the_publish_command_syncs_served_stylesheets(): void
    {
        $served = public_path('css/app.css');

        file_put_contents($served, '/* stale */');

        $this->artisan('assets:publish')->assertSuccessful();

        $this->assertSame(
            (string) file_get_contents(resource_path('css/app.css')),
            (string) file_get_contents($served),
        );
    }

    public function test_pages_link_versioned_stylesheets(): void
    {
        $this->get('/')
            ->assertOk()
            ->assertSee('css/app.css?v=', false);
    }
}
