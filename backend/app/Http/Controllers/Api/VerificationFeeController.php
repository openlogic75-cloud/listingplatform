<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\VerificationFeeSetting;
use Illuminate\Http\JsonResponse;

/**
 * Verification fee read for the app (M5.4). Display-only — the volunteer
 * collects the fee directly at the visit, never through this platform.
 */
class VerificationFeeController extends Controller
{
    public function show(): JsonResponse
    {
        $setting = VerificationFeeSetting::current();

        return response()->json([
            'data' => [
                'amount_inr' => $setting->amount_inr !== null ? (float) $setting->amount_inr : null,
                'currency_symbol' => config('app.currency_symbol', 'Rs.'),
            ],
        ]);
    }
}