<?php

namespace Tests\Feature;

use App\Models\DriverAvailability;
use App\Models\User;
use App\Support\BlindIndex;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class RegistrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_vendor_can_register_and_receives_a_token(): void
    {
        $response = $this->postJson('/api/v1/auth/register', [
            'name' => 'Meera Devi',
            'email' => 'meera@example.test',
            'phone' => '9876500011',
            'password' => 'secret1234',
            'password_confirmation' => 'secret1234',
            'accept_terms' => true,
            'role' => 'vendor',
            'display_name' => 'Meera Organic Farm',
            'vendor_category' => 'agro',
        ]);

        $response->assertCreated()
            ->assertJsonStructure(['token', 'user' => ['id', 'name', 'email', 'role']]);

        $this->assertDatabaseHas('users', ['role' => 'vendor']);
        $this->assertDatabaseHas('vendors', ['display_name' => 'Meera Organic Farm']);

        $user = User::query()->firstOrFail();
        $this->assertSame('meera@example.test', $user->email);
        $this->assertSame(BlindIndex::make('meera@example.test'), $user->email_index);
    }

    public function test_a_driver_registration_creates_an_availability_row(): void
    {
        $response = $this->postJson('/api/v1/auth/register', [
            'name' => 'Rider Ram',
            'email' => 'ram@example.test',
            'password' => 'secret1234',
            'password_confirmation' => 'secret1234',
            'accept_terms' => true,
            'role' => 'driver',
        ]);

        $response->assertCreated();

        $availability = DriverAvailability::query()->firstOrFail();

        $this->assertFalse($availability->is_online);
        $this->assertNull($availability->last_online_at);
    }

    public function test_buyers_cannot_register_because_they_browse_as_guests(): void
    {
        $response = $this->postJson('/api/v1/auth/register', [
            'name' => 'Guest Buyer',
            'email' => 'buyer@example.test',
            'password' => 'secret1234',
            'password_confirmation' => 'secret1234',
            'accept_terms' => true,
            'role' => 'buyer',
        ]);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors('role');
    }

    public function test_a_registered_user_can_login_and_fetch_their_profile(): void
    {
        $this->postJson('/api/v1/auth/register', [
            'name' => 'Rider Ram',
            'email' => 'ram@example.test',
            'password' => 'secret1234',
            'password_confirmation' => 'secret1234',
            'accept_terms' => true,
            'role' => 'driver',
        ]);

        $login = $this->postJson('/api/v1/auth/login', [
            'email' => 'ram@example.test',
            'password' => 'secret1234',
        ]);

        $login->assertOk()->assertJsonStructure(['token', 'user' => ['id', 'role']]);

        $me = $this->withToken((string) $login->json('token'))->getJson('/api/v1/auth/me');

        $me->assertOk()->assertJsonPath('user.email', 'ram@example.test');
    }

    public function test_pii_is_encrypted_at_rest_and_hashes_are_hidden(): void
    {
        $register = $this->postJson('/api/v1/auth/register', [
            'name' => 'Worker W',
            'email' => 'worker@example.test',
            'password' => 'secret1234',
            'password_confirmation' => 'secret1234',
            'accept_terms' => true,
            'role' => 'skilled_worker',
        ]);

        $register->assertCreated();

        // The user's own profile returns their email over HTTPS (by design),
        // but keyed blind-index hashes and the password hash never serialize.
        $this->assertStringNotContainsString('email_index', $register->getContent());
        $this->assertStringNotContainsString('phone_index', $register->getContent());
        $this->assertStringNotContainsString('$2y$', $register->getContent());

        $raw = DB::table('users')->first();

        // Laravel's encrypted cast stores a base64 JSON payload at rest.
        $this->assertStringStartsWith('eyJ', (string) $raw->email);
        $this->assertStringNotContainsString('worker@example.test', (string) $raw->email);
    }

    public function test_duplicate_registration_email_is_rejected(): void
    {
        $payload = [
            'name' => 'First Person',
            'email' => 'dup@example.test',
            'password' => 'secret1234',
            'password_confirmation' => 'secret1234',
            'accept_terms' => true,
            'role' => 'skilled_worker',
        ];

        $this->postJson('/api/v1/auth/register', $payload)->assertCreated();

        $payload['name'] = 'Second Person';
        $payload['email'] = 'DUP@example.test'; // case-insensitive via blind index

        $this->postJson('/api/v1/auth/register', $payload)
            ->assertUnprocessable()
            ->assertJsonValidationErrors('email');
    }

    /**
     * M10.3: the stored role value is `skilled_worker`; the old `worker`
     * string is no longer registerable.
     */
    public function test_the_legacy_worker_role_is_rejected(): void
    {
        $this->postJson('/api/v1/auth/register', [
            'name' => 'Old Worker',
            'email' => 'old-worker@example.test',
            'password' => 'secret1234',
            'password_confirmation' => 'secret1234',
            'accept_terms' => true,
            'role' => 'worker',
        ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('role');
    }
}
