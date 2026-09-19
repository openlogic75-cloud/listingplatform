<?php

namespace App\Services;

use App\Models\Referral;
use App\Models\ReferralEvent;
use App\Models\User;
use App\Models\Vendor;
use Illuminate\Support\Str;

/**
 * Peer-to-peer referral & affiliate tracking (M6.3, extended M21.2).
 *
 * A vendor or driver generates a code and sets the commission they will pay
 * an affiliate who brings them business — a percentage of the order value or
 * a fixed amount, their choice, per code. Counts and commission are
 * informational: the platform never moves money (Q1 default A); the owner
 * pays the affiliate directly.
 */
class ReferralService
{
    /**
     * Generate a unique code owned by a vendor or driver.
     */
    public function generateCode(
        Vendor|User $owner,
        ?string $label = null,
        string $commissionType = Referral::COMMISSION_PERCENT,
        float $commissionValue = 0,
    ): Referral {
        $base = $label
            ? Str::slug($label)
            : Str::lower(Str::random(6));

        $code = $base;
        $suffix = 1;

        while (Referral::query()->where('code', $code)->exists()) {
            $code = $base.'-'.$suffix;
            $suffix++;
        }

        return Referral::query()->create([
            'vendor_id' => $owner instanceof Vendor ? $owner->id : null,
            'owner_user_id' => $owner instanceof Vendor ? $owner->user_id : $owner->id,
            'code' => $code,
            'commission_type' => in_array($commissionType, Referral::COMMISSION_TYPES, true)
                ? $commissionType
                : Referral::COMMISSION_PERCENT,
            'commission_value' => max(0, $commissionValue),
        ]);
    }

    /**
     * Record a signup attributed to a referral code.
     */
    public function recordSignup(string $code, int $userId): ?ReferralEvent
    {
        $referral = Referral::query()->where('code', $code)->first();

        if ($referral === null) {
            return null;
        }

        $event = ReferralEvent::query()->create([
            'referral_id' => $referral->id,
            'type' => ReferralEvent::TYPE_SIGNUP,
            'attributed_user_id' => $userId,
        ]);

        $referral->increment('signups_count');

        return $event;
    }

    /**
     * Record a conversion (a completed booking or errand) attributed to a
     * code. The commission is snapshotted from the owner's rule at this
     * moment so later changes never rewrite history.
     */
    public function recordConversion(
        string $code,
        ?int $userId = null,
        ?float $orderValue = null,
    ): ?ReferralEvent {
        $referral = Referral::query()->where('code', $code)->first();

        if ($referral === null) {
            return null;
        }

        $event = ReferralEvent::query()->create([
            'referral_id' => $referral->id,
            'type' => ReferralEvent::TYPE_CONVERSION,
            'status' => ReferralEvent::STATUS_PENDING,
            'attributed_user_id' => $userId,
            'order_value' => $orderValue,
            'amount_inr' => $referral->commissionFor($orderValue),
        ]);

        $referral->increment('conversions_count');

        return $event;
    }

    /**
     * Owner approval of a recorded conversion (M21.2). Only then does the
     * commission count — the owner decides who they will pay, since the
     * platform never moves money.
     */
    public function approveConversion(ReferralEvent $event, User $owner): ReferralEvent
    {
        $event->update([
            'status' => ReferralEvent::STATUS_APPROVED,
            'approved_by' => $owner->id,
            'approved_at' => now(),
        ]);

        return $event;
    }

    public function rejectConversion(ReferralEvent $event, User $owner): ReferralEvent
    {
        $event->update([
            'status' => ReferralEvent::STATUS_REJECTED,
            'approved_by' => $owner->id,
            'approved_at' => now(),
        ]);

        return $event;
    }

    /**
     * Codes owned by a user (vendor or driver) with their ledger totals and
     * the conversions waiting for the owner's approval.
     *
     * @return array<string, mixed>
     */
    public function statsForOwner(User $user): array
    {
        $referrals = Referral::query()
            ->where('owner_user_id', $user->id)
            ->latest('id')
            ->get();

        $referralIds = $referrals->pluck('id');

        $conversions = ReferralEvent::query()
            ->whereIn('referral_id', $referralIds)
            ->where('type', ReferralEvent::TYPE_CONVERSION)
            ->with('referral')
            ->latest('id')
            ->get();

        return [
            'codes_count' => $referrals->count(),
            'total_signups' => $referrals->sum('signups_count'),
            'total_conversions' => $referrals->sum('conversions_count'),
            // Only owner-approved conversions count as a commission; a
            // rejected one is dropped from the total.
            'total_earnings' => round((float) $conversions
                ->where('status', ReferralEvent::STATUS_APPROVED)
                ->sum('amount_inr'), 2),
            'pending_earnings' => round((float) $conversions
                ->where('status', ReferralEvent::STATUS_PENDING)
                ->sum('amount_inr'), 2),
            'pending_count' => $conversions
                ->where('status', ReferralEvent::STATUS_PENDING)
                ->count(),
            'pending' => $conversions
                ->where('status', ReferralEvent::STATUS_PENDING)
                ->map(fn (ReferralEvent $event) => [
                    'id' => $event->id,
                    'code' => $event->referral?->code,
                    'order_value' => (float) $event->order_value,
                    'amount' => (float) $event->amount_inr,
                    'recorded_at' => $event->created_at,
                ])
                ->values()
                ->all(),
            'codes' => $referrals->map(fn (Referral $r) => [
                'id' => $r->id,
                'code' => $r->code,
                'commission_type' => $r->commission_type,
                'commission_value' => $r->commission_value,
                'commission_label' => $r->commissionLabel(),
                'signups_count' => $r->signups_count,
                'conversions_count' => $r->conversions_count,
                'created_at' => $r->created_at,
            ]),
        ];
    }

    /**
     * Backwards-compatible vendor stats (M6.3 callers/tests).
     *
     * @return array<string, mixed>
     */
    public function stats(Vendor $vendor): array
    {
        return $this->statsForOwner($vendor->user);
    }
}
