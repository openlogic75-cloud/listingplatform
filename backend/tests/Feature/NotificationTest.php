<?php

namespace Tests\Feature;

use App\Models\DeviceToken;
use App\Models\User;
use App\Support\BlindIndex;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification as NotificationFacade;
use Tests\TestCase;

class NotificationTest extends TestCase
{
    use RefreshDatabase;

    private function makeUser(string $email = 'user@test.com', string $role = 'vendor'): User
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

    public function test_a_user_can_register_a_device_token(): void
    {
        $user = $this->makeUser();

        $response = $this->actingAs($user, 'sanctum')
            ->postJson('/api/v1/device-tokens', [
                'token' => 'fcm-token-abc123',
                'platform' => 'android',
            ]);

        $response->assertCreated()
            ->assertJsonPath('data.token', 'fcm-token-abc123')
            ->assertJsonPath('data.platform', 'android');

        $this->assertDatabaseHas('device_tokens', [
            'user_id' => $user->id,
            'token' => 'fcm-token-abc123',
            'platform' => 'android',
        ]);
    }

    public function test_a_user_can_remove_a_device_token(): void
    {
        $user = $this->makeUser();

        DeviceToken::query()->create([
            'user_id' => $user->id,
            'token' => 'fcm-token-toremove',
            'platform' => 'android',
        ]);

        $response = $this->actingAs($user, 'sanctum')
            ->deleteJson('/api/v1/device-tokens/fcm-token-toremove');

        $response->assertOk()
            ->assertJsonPath('message', 'Device token removed.');

        $this->assertDatabaseMissing('device_tokens', [
            'user_id' => $user->id,
            'token' => 'fcm-token-toremove',
        ]);
    }

    public function test_a_user_cannot_remove_someone_elses_token(): void
    {
        $owner = $this->makeUser('owner@test.com');
        $other = $this->makeUser('other@test.com');

        DeviceToken::query()->create([
            'user_id' => $owner->id,
            'token' => 'fcm-token-owner',
            'platform' => 'android',
        ]);

        $response = $this->actingAs($other, 'sanctum')
            ->deleteJson('/api/v1/device-tokens/fcm-token-owner');

        $response->assertStatus(422);
    }

    public function test_booking_status_change_writes_in_app_notification(): void
    {
        NotificationFacade::fake();

        $user = $this->makeUser('vendor@test.com', 'vendor');
        $district = \App\Models\District::query()->create(['name' => 'Test District', 'is_active' => true]);
        $vendor = \App\Models\Vendor::query()->create([
            'user_id' => $user->id,
            'display_name' => 'Test Shop',
            'category' => 'traditional',
            'district_id' => $district->id,
        ]);

        $booking = \App\Models\Booking::query()->create([
            'code' => 'BK-NOTIF01',
            'vendor_id' => $vendor->id,
            'status' => 'pending',
            'contact_name' => 'Guest',
            'contact_phone' => '+555111',
            'contact_phone_index' => BlindIndex::make('+555111'),
            'settled_offline' => true,
        ]);

        $service = new \App\Services\BookingService();
        $service->changeStatus($booking, 'confirmed');

        NotificationFacade::assertSentTo(
            $user,
            \App\Notifications\GenericNotification::class
        );
    }
}