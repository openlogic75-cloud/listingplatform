<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * M7.4 - Security hardening pass: security headers, throttling on web write
 * endpoints, and role gating on the admin dashboard.
 */
class SecurityHardeningTest extends TestCase
{
    use RefreshDatabase;

    public function test_security_headers_are_present_on_every_response(): void
    {
        $this->get('/')
            ->assertHeader('X-Content-Type-Options', 'nosniff')
            ->assertHeader('X-Frame-Options', 'DENY')
            ->assertHeader('X-XSS-Protection', '0')
            ->assertHeader('Referrer-Policy', 'strict-origin-when-cross-origin')
            ->assertHeader('Cross-Origin-Opener-Policy', 'same-origin')
            ->assertHeader('Strict-Transport-Security', 'max-age=31536000; includeSubDomains');
    }

    public function test_strict_csp_blocks_inline_scripts(): void
    {
        $this->get('/')->assertHeader(
            'Content-Security-Policy',
            "default-src 'self'; script-src 'self'; style-src 'self' 'unsafe-inline'; img-src 'self' data: blob:; font-src 'self'; connect-src 'self'"
        );
    }

    public function test_dashboard_is_inaccessible_to_anonymous_and_non_admin(): void
    {
        $this->get('/admin/donation')->assertRedirect(route('admin.login'));

        $vendor = \App\Models\User::query()->create([
            'name' => 'Vendor',
            'email' => 'vendor-sec@test.com',
            'email_index' => \App\Support\BlindIndex::make('vendor-sec@test.com'),
            'password' => bcrypt('Password123!'),
            'role' => \App\Models\User::ROLE_VENDOR,
            'is_active' => true,
        ]);

        $this->actingAs($vendor)->get('/admin/donation')->assertForbidden();
    }
}