<?php

namespace Tests\Feature;

use App\Models\Consent;
use App\Models\User;
use App\Support\BlindIndex;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * M26: legal pages and DPDP consent capture at registration.
 */
class LegalComplianceTest extends TestCase
{
    use RefreshDatabase;

    public function test_the_legal_pages_render(): void
    {
        $this->get(route('terms'))->assertOk()->assertSee('Terms & Conditions');
        $this->get(route('privacy'))->assertOk()->assertSee('Privacy Policy')->assertSee('DPDP');
        $this->get(route('disclaimer'))->assertOk()->assertSee('Disclaimer');
    }

    public function test_registration_requires_accepting_the_terms(): void
    {
        $this->postJson('/api/v1/auth/register', [
            'name' => 'No Consent',
            'email' => 'noconsent@test.com',
            'password' => 'secret1234',
            'password_confirmation' => 'secret1234',
            'role' => User::ROLE_DRIVER,
        ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('accept_terms');

        $this->assertDatabaseCount('users', 0);
    }

    public function test_registration_records_consent_with_the_notice_version(): void
    {
        $this->postJson('/api/v1/auth/register', [
            'name' => 'Consenting Driver',
            'email' => 'consent@test.com',
            'password' => 'secret1234',
            'password_confirmation' => 'secret1234',
            'role' => User::ROLE_DRIVER,
            'accept_terms' => true,
        ])->assertCreated();

        $user = User::query()->where('email_index', BlindIndex::make('consent@test.com'))->firstOrFail();

        $this->assertDatabaseHas('consents', [
            'subject_type' => 'user',
            'subject_id' => $user->id,
            'consent_key' => Consent::KEY_REGISTRATION,
            'text_version' => (string) config('legal.consent_version'),
        ]);
    }

    public function test_the_web_registration_form_shows_the_consent_checkbox(): void
    {
        $this->get(route('register'))
            ->assertOk()
            ->assertSee('accept_terms', false)
            ->assertSee(route('terms'), false)
            ->assertSee(route('privacy'), false);
    }
}
