<?php

namespace App\Http\Controllers\Web\Admin;

use App\Http\Controllers\Controller;
use App\Models\DonationSetting;
use App\Support\UploadValidator;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/**
 * Admin donation settings (M6.2). Single settings row: UPI ID + QR image.
 * QR upload re-validated via the M2.3 validator; UPI ID format-checked.
 */
class DonationSettingsController extends Controller
{
    public function edit()
    {
        $setting = DonationSetting::query()->first();

        return view('admin.donation.edit', [
            'setting' => $setting,
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'upi_id' => ['nullable', 'string', 'max:100', 'regex:/^[a-zA-Z0-9._-]+@[a-zA-Z0-9._-]+$/'],
            'qr_image' => ['nullable', 'file', 'max:'.UploadValidator::MAX_KILOBYTES],
        ]);

        $setting = DonationSetting::query()->first();

        if ($setting === null) {
            $setting = new DonationSetting;
        }

        $setting->upi_id = $data['upi_id'] ?? null;

        if ($request->hasFile('qr_image')) {
            // The QR stays lossless PNG (M11.1): a lossy WebP could hurt
            // scannability, and this is a single small image.
            $result = UploadValidator::validateAndStore(
                $request->file('qr_image'),
                'upi',
                convertToWebp: false,
            );

            $setting->qr_path = $result['path'];
        }

        $setting->save();

        return redirect()->back()->with('status', 'Donation settings updated.');
    }
}
