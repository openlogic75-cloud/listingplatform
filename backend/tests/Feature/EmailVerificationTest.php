<?php

namespace Tests\Feature;

use App\Models\User;
use App\Support\BlindIndex;
use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\URL;
use Tests\TestCase;

/**
 * M45.1: production registration requires clicking the signed email link.
 */
class EmailVerificationTest extends TestCase
{
    use RefreshDatabase;

    public function test_registration_sends_verification_and_blocks_login_until_clicked(): void
    {
        config()->set('app.require_email_verification', true);
        Notification::fake();

        $registration = $this->postJson('/api/v1/auth/register', [
            'name' => 'Verified Vendor',
            'email' => 'verified@example.test',
            'password' => 'secret1234',
            'password_confirmation' => 'secret1234',
            'accept_terms' => true,
            'role' => 'vendor',
            'display_name' => 'Verified Shop',
            'vendor_category' => 'agro',
        ]);

        $registration->assertAccepted()
            ->assertJsonPath('email_verified', false);

        $user = User::query()
            ->where('email_index', BlindIndex::make('verified@example.test'))
            ->firstOrFail();

        Notification::assertSentTo($user, VerifyEmail::class);

        $this->postJson('/api/v1/auth/login', [
            'email' => 'verified@example.test',
            'password' => 'secret1234',
        ])->assertForbidden();

        $verificationUrl = URL::temporarySignedRoute(
            'verification.verify',
            now()->addMinutes(10),
            [
                'id' => $user->id,
                'hash' => sha1($user->getEmailForVerification()),
            ],
        );

        $this->get($verificationUrl)
            ->assertRedirect(route('login'));

        $this->assertNotNull($user->fresh()->email_verified_at);

        $this->postJson('/api/v1/auth/login', [
            'email' => 'verified@example.test',
            'password' => 'secret1234',
        ])->assertOk();
    }
}
