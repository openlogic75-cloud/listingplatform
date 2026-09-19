<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Referral;
use App\Services\ReferralService;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

/**
 * Referral landing page (M6.3). Attributes signups to a vendor's referral
 * code. The landing page URL is shared by the vendor's marketing team.
 */
class ReferralLandingController extends Controller
{
    public function __construct(private ReferralService $referrals)
    {
    }

    /**
     * Show the landing page for a referral code.
     */
    public function show(Request $request, string $code)
    {
        $referral = Referral::query()
            ->where('code', $code)
            ->with('vendor')
            ->first();

        if ($referral === null) {
            abort(404);
        }

        // Store the code in the session so registration can attribute the signup.
        $request->session()->put('referral_code', $code);

        return view('pages.referral', [
            'referral' => $referral,
            'vendor' => $referral->vendor,
        ]);
    }

    /**
     * API endpoint to attribute a signup (called after registration).
     */
    public function attribute(Request $request): JsonResponse
    {
        $data = $request->validate([
            'code' => ['required', 'string', 'max:60'],
            'user_id' => ['required', 'integer'],
        ]);

        $this->referrals->recordSignup($data['code'], $data['user_id']);

        return response()->json(['status' => 'attributed']);
    }
}
