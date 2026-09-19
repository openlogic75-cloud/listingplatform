<?php

namespace Tests\Feature;

use App\Models\Consent;
use App\Models\DataRequest;
use App\Models\User;
use App\Support\BlindIndex;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DpdpTest extends TestCase
{
    use RefreshDatabase;

    private function makeUser(string $email = 'user@test.com', string $role = 'buyer'): User
    {
        return User::query()->create([
            'name' => 'Test User',
            'email' => $email,
            'email_index' => BlindIndex::make($email),
            'password' => bcrypt('Password123!'),
            'role' => $role,
            'is_active' => true,
        ]);
    }

    public function test_a_user_can_record_consent(): void
    {
        $user = $this->makeUser();

        $response = $this->actingAs($user, 'sanctum')
            ->postJson('/api/v1/consents', [
                'consent_key' => Consent::KEY_REGISTRATION,
                'text_version' => '1.0',
                'purpose' => 'Create an account and use the platform',
                'subject_type' => User::class,
                'subject_id' => $user->id,
            ]);

        $response->assertCreated()
            ->assertJsonPath('data.consent_key', Consent::KEY_REGISTRATION);
    }

    public function test_a_user_can_list_their_consents(): void
    {
        $this->withoutExceptionHandling();
        $user = $this->makeUser();

        $consent = Consent::query()->create([
            'subject_type' => User::class,
            'subject_id' => $user->id,
            'consent_key' => Consent::KEY_REGISTRATION,
            'text_version' => '1.0',
            'purpose' => 'Registration',
            'granted_at' => now(),
            'revoked_at' => null,
        ]);

        $response = $this->actingAs($user, 'sanctum')
            ->getJson('/api/v1/consents');

        $response->assertOk()
            ->assertJsonCount(1, 'data');
    }

    public function test_a_user_can_revoke_consent(): void
    {
        $user = $this->makeUser();

        $consent = Consent::query()->create([
            'subject_type' => User::class,
            'subject_id' => (int) $user->id,
            'consent_key' => Consent::KEY_NOTIFICATIONS,
            'text_version' => '1.0',
            'purpose' => 'Notifications',
            'granted_at' => now(),
        ]);

        $response = $this->actingAs($user, 'sanctum')
            ->deleteJson("/api/v1/consents/{$consent->id}");

        $response->assertOk()
            ->assertJsonPath('data.revoked_at', fn ($v) => $v !== null);
    }

    public function test_a_user_can_export_their_data(): void
    {
        $user = $this->makeUser('export@test.com');

        $response = $this->actingAs($user, 'sanctum')
            ->postJson('/api/v1/export');

        $response->assertCreated()
            ->assertJsonPath('data.status', DataRequest::STATUS_COMPLETED)
            ->assertJsonPath('data.download_url', fn ($v) => $v !== null);

        $this->assertDatabaseHas('data_requests', [
            'user_id' => $user->id,
            'type' => DataRequest::TYPE_EXPORT,
            'status' => DataRequest::STATUS_COMPLETED,
        ]);
    }

    public function test_a_user_can_request_deletion(): void
    {
        $user = $this->makeUser('delete@test.com');

        $response = $this->actingAs($user, 'sanctum')
            ->postJson('/api/v1/deletion');

        $response->assertCreated()
            ->assertJsonPath('data.status', DataRequest::STATUS_COMPLETED);

        // Verify the user's PII is anonymized.
        $user->refresh();
        $this->assertEquals('Deleted User', $user->name);
        $this->assertNull($user->phone);
        $this->assertStringContainsString('deleted-'.$user->id, $user->email);

        $this->assertDatabaseHas('data_requests', [
            'user_id' => $user->id,
            'type' => DataRequest::TYPE_DELETION,
            'status' => DataRequest::STATUS_COMPLETED,
        ]);
    }
}
