<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\DonationSetting;
use Illuminate\Http\JsonResponse;

/**
 * Donation settings for the app (M6.1). Returns the admin-managed UPI ID
 * and QR image URL. The platform stores no payment data — display-only.
 */
class DonationController extends Controller
{
    public function show(): JsonResponse
    {
        $setting = DonationSetting::query()->first();

        return response()->json([
            'data' => $setting ? [
                'upi_id' => $setting->upi_id,
                'qr_url' => $setting->qr_path ? \Storage::disk('public')->url($setting->qr_path) : null,
                'upi_deep_link' => $setting->upi_id ? 'upi://pay?pa='.urlencode($setting->upi_id).'&pn=ListingPlatform&cu=INR' : null,
            ] : null,
        ]);
    }
}
