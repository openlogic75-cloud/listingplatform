<?php

namespace Tests\Feature;

use App\Mail\AdminPasswordOtpMail;
use App\Models\PasswordChangeOtp;
use App\Models\User;
use App\Support\BlindIndex;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

/**
 * M38.1: an admin changes their password only after confirming an email OTP.
 */
class AdminPasswordTest extends TestCase
{
    use RefreshDatabase;

    private function admin(string $email = 'contact@shekuthi.in'): User
    {
        return User::query()->create([
            'name' => 'K Hika Zhimomi',
            'email' => $email,
            'email_index' => BlindIndex::make($email),
            'password' => bcrypt('Temporary123!'),
            'role' => User::ROLE_ADMIN,
            'is_active' => true,
        ]);
    }

    public function test_admin_requests_an_otp_and_changes_password_after_confirmation(): void
    {
        Mail::fake();
        $admin = $this->admin();

        $this->actingAs($admin)
            ->get(route('admin.password.edit'))
            ->assertOk()
            ->assertSee('Change password');

        $this->actingAs($admin)
            ->post(route('admin.password.request'), [
                'new_password' => 'NewPassword123!',
                'new_password_confirmation' => 'NewPassword123!',
            ])
            ->assertRedirect(route('admin.password.edit'));

        $challenge = PasswordChangeOtp::query()->firstOrFail();
        $this->assertTrue(Hash::check('NewPassword123!', $challenge->new_password_hash));
        $this->assertFalse(Hash::check('NewPassword123!', $admin->fresh()->password));

        $otp = null;
        Mail::assertSent(AdminPasswordOtpMail::class, function (AdminPasswordOtpMail $mail) use (&$otp, $admin): bool {
            $otp = $mail->otp;

            return $mail->hasTo($admin->email);
        });

        $this->actingAs($admin)
            ->post(route('admin.password.confirm'), ['otp' => $otp])
            ->assertRedirect(route('admin.password.edit'));

        $this->assertTrue(Hash::check('NewPassword123!', $admin->fresh()->password));
        $this->assertNotNull($challenge->fresh()->consumed_at);
    }

    public function test_wrong_otp_does_not_change_the_password(): void
    {
        Mail::fake();
        $admin = $this->admin('wrong-otp@shekuthi.in');

        $this->actingAs($admin)->post(route('admin.password.request'), [
            'new_password' => 'AnotherPassword123!',
            'new_password_confirmation' => 'AnotherPassword123!',
        ]);

        $this->actingAs($admin)
            ->post(route('admin.password.confirm'), ['otp' => '000000'])
            ->assertSessionHasErrors('otp');

        $this->assertTrue(Hash::check('Temporary123!', $admin->fresh()->password));
        $this->assertSame(1, PasswordChangeOtp::query()->firstOrFail()->attempts);
    }
}
