<?php

namespace App\Http\Controllers\Web\Admin;

use App\Http\Controllers\Controller;
use App\Mail\AdminPasswordOtpMail;
use App\Models\PasswordChangeOtp;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\Rules\Password;
use Illuminate\View\View;

class PasswordController extends Controller
{
    public function edit(): View
    {
        return view('admin.password');
    }

    public function requestOtp(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'new_password' => ['required', 'confirmed', Password::min(8)],
        ]);

        $admin = $request->user();
        PasswordChangeOtp::query()
            ->where('user_id', $admin->id)
            ->whereNull('consumed_at')
            ->delete();

        $otp = (string) random_int(100000, 999999);
        $challenge = PasswordChangeOtp::query()->create([
            'user_id' => $admin->id,
            'otp_hash' => Hash::make($otp),
            'new_password_hash' => Hash::make($data['new_password']),
            'expires_at' => now()->addMinutes(10),
        ]);

        Mail::to($admin->email)->send(new AdminPasswordOtpMail(
            $otp,
            $challenge->expires_at->format('j M Y, H:i T'),
        ));

        $request->session()->put('admin_password_otp_id', $challenge->id);

        return redirect()
            ->route('admin.password.edit')
            ->with('status', 'A password change code was sent to your admin email.');
    }

    public function confirm(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'otp' => ['required', 'digits:6'],
        ]);

        $challenge = PasswordChangeOtp::query()
            ->whereKey($request->session()->get('admin_password_otp_id'))
            ->where('user_id', $request->user()->id)
            ->whereNull('consumed_at')
            ->first();

        if ($challenge === null || $challenge->expires_at->isPast() || $challenge->attempts >= 5) {
            return back()->withErrors(['otp' => 'This password change code is no longer valid. Request a new code.']);
        }

        if (! Hash::check($data['otp'], $challenge->otp_hash)) {
            $challenge->increment('attempts');

            return back()->withErrors(['otp' => 'That password change code is incorrect.']);
        }

        $request->user()->forceFill([
            'password' => $challenge->new_password_hash,
        ])->save();

        $challenge->update(['consumed_at' => now()]);
        $request->session()->forget('admin_password_otp_id');

        return redirect()
            ->route('admin.password.edit')
            ->with('status', 'Your admin password was changed.');
    }
}
