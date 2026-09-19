<?php

namespace Tests\Feature;

use Tests\TestCase;

/**
 * M33: public contact, grievance and peer-to-peer responsibility copy.
 */
class ContactAndLegalTest extends TestCase
{
    public function test_contact_page_shows_general_and_grievance_details(): void
    {
        $this->get(route('contact'))
            ->assertOk()
            ->assertSee('contact@shekuthi.in')
            ->assertSee('K Hika Zhimomi')
            ->assertSee('Peer-to-peer arrangements');
    }

    public function test_about_and_disclaimer_show_contact_and_rate_responsibility(): void
    {
        $this->get(route('about'))
            ->assertOk()
            ->assertSee('contact@shekuthi.in')
            ->assertSee('K Hika Zhimomi');

        $this->get(route('disclaimer'))
            ->assertOk()
            ->assertSee('Peer-to-peer rates and compliance')
            ->assertSee('district, state, local-jurisdiction or municipal')
            ->assertSee('K Hika Zhimomi')
            ->assertSee('contact@shekuthi.in');
    }
}
