<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\District;
use App\Services\ReferralService;
use App\Services\RegistrationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

/**
 * Website registration (M8.6). Same rules as the app: the four registerable
 * roles only; buyers stay anonymous (Q3).
 */
class MemberRegistrationController extends Controller
{
    public function __construct(
        private RegistrationService $registrations,
        private ReferralService $referrals,
    ) {}

    public function showRegister(): View|RedirectResponse
    {
        if (Auth::check()) {
            return redirect()->route('dashboard');
        }

        return view('auth.register', [
            'districts' => District::query()
                ->where('is_active', true)
                ->orderBy('name')
                ->get(),
        ]);
    }

    public function register(Request $request): RedirectResponse
    {
        $user = $this->registrations->register($request->all());

        // M9.3: attribute the signup when the visitor arrived through a
        // vendor's shared link (?ref=) or the referral landing page. Unknown
        // codes are a no-op (recordSignup returns null); the key pops so a
        // later user on the same session is never misattributed.
        $code = $request->session()->pull('referral_code');

        if (is_string($code) && $code !== '') {
            $this->referrals->recordSignup($code, $user->id);
        }

        Auth::login($user);
        $request->session()->regenerate();

        return redirect()->route('dashboard');
    }
}
