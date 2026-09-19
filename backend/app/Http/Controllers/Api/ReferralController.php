<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\ReferralService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

/**
 * Vendor referral management (M6.3). Vendors generate codes and view stats.
 * The ReferralService owns all tracking logic.
 */
class ReferralController extends Controller
{
    public function __construct(private ReferralService $referrals)
    {
    }

    /**
     * List vendor's referral codes + stats.
     */
    public function index(Request $request): JsonResponse
    {
        $vendor = $request->user()?->vendor;

        if ($vendor === null) {
            return response()->json(['data' => []]);
        }

        $stats = $this->referrals->stats($vendor);

        return response()->json(['data' => $stats]);
    }

    /**
     * Generate a new referral code.
     */
    public function store(Request $request): JsonResponse
    {
        $vendor = $request->user()?->vendor;

        if ($vendor === null) {
            throw ValidationException::withMessages([
                'role' => ['Only vendors can create referral codes.'],
            ]);
        }

        $data = $request->validate([
            'label' => ['nullable', 'string', 'max:40'],
        ]);

        $referral = $this->referrals->generateCode($vendor, $data['label'] ?? null);

        return response()->json([
            'data' => [
                'id' => $referral->id,
                'code' => $referral->code,
                'signups_count' => 0,
                'conversions_count' => 0,
                'created_at' => $referral->created_at,
            ],
        ], 201);
    }
}
