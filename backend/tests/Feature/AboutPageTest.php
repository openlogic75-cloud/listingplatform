<?php

namespace Tests\Feature;

use Tests\TestCase;

/**
 * M29.1: the About page points to the public source and invites contributions.
 */
class AboutPageTest extends TestCase
{
    public function test_about_page_links_to_the_open_source_repository(): void
    {
        config()->set('branding.repository_url', 'https://example.test/source');

        $this->get(route('about'))
            ->assertOk()
            ->assertSee('<title>About - Shekuthi</title>', false)
            ->assertSee('Open source')
            ->assertSee('MIT licence')
            ->assertSee('https://example.test/source');
    }

    public function test_about_page_discloses_ai_assisted_build(): void
    {
        $this->get(route('about'))
            ->assertOk()
            ->assertSee('Built with AI')
            ->assertSee('OpenCode')
            ->assertSee('GPT')
            ->assertSee('GLM')
            ->assertSee('DeepSeek');
    }
}
