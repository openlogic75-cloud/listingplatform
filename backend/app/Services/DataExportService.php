<?php

namespace App\Services;

use App\Models\Consent;
use App\Models\User;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * Self-serve data export (M7.2). Machine-readable export of all personal data
 * including decrypted PII. One service class so new tables are added to the
 * export map only.
 */
class DataExportService
{
    /**
     * Generate a data export for a user. Returns the file path.
     */
    public function export(User $user): string
    {
        $data = [
            'user' => $this->exportUser($user),
            'consents' => $this->exportConsents($user),
            'exported_at' => now()->toIso8601String(),
        ];

        $filename = 'exports/'.Str::uuid().'.json';
        Storage::disk('local')->put($filename, json_encode($data, JSON_PRETTY_PRINT));

        return $filename;
    }

    private function exportUser(User $user): array
    {
        return [
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'phone' => $user->phone,
            'role' => $user->role,
            'created_at' => $user->created_at?->toIso8601String(),
            'vendor' => $user->vendor ? [
                'display_name' => $user->vendor->display_name,
                'category' => $user->vendor->category,
            ] : null,
        ];
    }

    private function exportConsents(User $user): array
    {
        return Consent::query()
            ->where('subject_type', User::class)
            ->where('subject_id', $user->id)
            ->get()
            ->map(fn ($c) => [
                'consent_key' => $c->consent_key,
                'text_version' => $c->text_version,
                'purpose' => $c->purpose,
                'granted_at' => $c->granted_at?->toIso8601String(),
                'revoked_at' => $c->revoked_at?->toIso8601String(),
            ])
            ->toArray();
    }
}
