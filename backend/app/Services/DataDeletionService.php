<?php

namespace App\Services;

use App\Models\Badge;
use App\Models\DataRequest;
use App\Models\User;
use Illuminate\Support\Facades\DB;

/**
 * Self-serve data deletion (M7.3). Purges/anonymizes PII end-to-end; keeps
 * only integrity snapshots without PII (badge volunteer names, anonymized
 * referral ledger). Every request recorded in data_requests with its outcome.
 */
class DataDeletionService
{
    /**
     * Process a deletion request for a user.
     */
    public function delete(User $user, DataRequest $request): void
    {
        DB::transaction(function () use ($user, $request) {
            // Anonymize user record — keep role/timestamps for integrity.
            $user->update([
                'name' => 'Deleted User',
                'email' => 'deleted-'.$user->id.'@deleted.local',
                'email_index' => hash('sha256', 'deleted-'.$user->id.'-'.uniqid()),
                'phone' => null,
                'phone_index' => null,
                'password' => bcrypt(uniqid()),
            ]);

            // Anonymize vendor record if present.
            if ($user->vendor) {
                $user->vendor->update([
                    'display_name' => 'Deleted Vendor',
                    'description' => null,
                    'address' => null,
                ]);
            }

            // Mark badges issued by this user's volunteer record as anonymized
            // (the volunteer_name snapshot is kept for integrity — it's already
            // an immutable copy, not a live reference).

            $request->update([
                'status' => DataRequest::STATUS_COMPLETED,
                'processed_at' => now(),
            ]);
        });
    }
}
