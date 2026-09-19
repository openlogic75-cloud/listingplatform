<?php

namespace App\Http\Controllers\Web\Admin;

use App\Http\Controllers\Controller;
use App\Models\VerificationFeeSetting;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/**
 * Admin verification fee settings (M5.4). Single admin-set amount in INR;
 * display-only — the platform never handles money, the volunteer collects the
 * fee directly at the visit.
 */
class VerificationFeeSettingsController extends Controller
{
    public function edit()
    {
        $setting = VerificationFeeSetting::current();

        return view('admin.verification-fee.edit', [
            'setting' => $setting,
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'amount_inr' => ['nullable', 'numeric', 'min:0', 'max:100000'],
        ]);

        VerificationFeeSetting::current()->update([
            'amount_inr' => isset($data['amount_inr']) && $data['amount_inr'] !== '' ? $data['amount_inr'] : null,
        ]);

        return redirect()->back()->with('status', 'Verification fee updated.');
    }
}