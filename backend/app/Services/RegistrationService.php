<?php

namespace App\Services;

use App\Models\Consent;
use App\Models\DriverAvailability;
use App\Models\User;
use App\Models\Vendor;
use App\Models\VerificationVolunteer;
use App\Models\WorkerProfile;
use App\Support\BlindIndex;
use Closure;
use Illuminate\Validation\Rules\Password;

/**
 * Single registration path for the four registerable roles (M1.4, M8.6).
 *
 * Both the app (API RegistrationController) and the website (web
 * MemberRegistrationController) go through this service so the role rules
 * and per-role profile rows can never fork. Buyers never register (Q3).
 */
class RegistrationService
{
    /**
     * Validate and create the user plus its per-role profile row.
     */
    public function register(array $attributes): User
    {
        $data = validator($attributes, [
            'name' => ['required', 'string', 'max:120'],
            'email' => [
                'required',
                'string',
                'email',
                'max:180',
                function (string $attribute, mixed $value, Closure $fail) {
                    if (User::query()->where('email_index', BlindIndex::make((string) $value))->exists()) {
                        $fail('This email is already registered.');
                    }
                },
            ],
            'phone' => [
                'nullable',
                'string',
                'max:20',
                function (string $attribute, mixed $value, Closure $fail) {
                    if ($value !== null && User::query()->where('phone_index', BlindIndex::make((string) $value))->exists()) {
                        $fail('This phone number is already registered.');
                    }
                },
            ],
            'password' => ['required', 'confirmed', Password::min(8)],
            // DPDP: registration needs a clear affirmative acceptance of the
            // Terms and the Privacy Policy (M26.1).
            'accept_terms' => ['accepted'],
            'role' => ['required', 'string', 'in:'.implode(',', User::REGISTERABLE_ROLES)],
            'district_id' => ['nullable', 'integer', 'exists:districts,id'],
            'display_name' => ['required_if:role,vendor', 'string', 'max:120'],
            'vendor_category' => ['required_if:role,vendor', 'string', 'in:traditional,agro,rental_homestay'],
        ])->validate();

        $user = User::query()->create([
            'name' => $data['name'],
            'email' => $data['email'],
            'email_index' => BlindIndex::make($data['email']),
            'phone' => $data['phone'] ?? null,
            'phone_index' => isset($data['phone']) ? BlindIndex::make($data['phone']) : null,
            'password' => $data['password'],
            'role' => $data['role'],
            'district_id' => $data['district_id'] ?? null,
        ]);

        match ($data['role']) {
            User::ROLE_VENDOR => Vendor::query()->create([
                'user_id' => $user->id,
                'display_name' => $data['display_name'],
                'category' => $data['vendor_category'],
                'district_id' => $data['district_id'] ?? null,
            ]),
            User::ROLE_DRIVER, User::ROLE_COLLECTOR => DriverAvailability::query()->create([
                'user_id' => $user->id,
            ]),
            User::ROLE_VOLUNTEER => VerificationVolunteer::query()->create([
                'user_id' => $user->id,
                'verification_status' => VerificationVolunteer::STATUS_PENDING,
            ]),
            User::ROLE_SKILLED_WORKER => WorkerProfile::query()->create([
                'user_id' => $user->id,
            ]),
            default => null,
        };

        // Record the consent the user gave at sign-up, with the notice
        // version so the exact text can be reproduced later (DPDP M26.1).
        Consent::query()->create([
            'subject_type' => 'user',
            'subject_id' => $user->id,
            'consent_key' => Consent::KEY_REGISTRATION,
            'text_version' => (string) config('legal.consent_version'),
            'purpose' => 'Create and manage your account and show what you choose to list. You accepted the Terms and the Privacy Policy.',
            'granted_at' => now(),
        ]);

        return $user;
    }
}
