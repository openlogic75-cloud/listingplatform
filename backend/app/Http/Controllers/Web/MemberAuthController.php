<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Support\BlindIndex;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

/**
 * Website sign-in for the member roles (M8.6). Session + CSRF, mirroring the
 * admin dashboard pattern. Administrators keep the separate /admin/login.
 */
class MemberAuthController extends Controller
{
    public function showLogin(): View|RedirectResponse
    {
        if (Auth::check()) {
            return $this->home(Auth::user());
        }

        return view('auth.login');
    }

    public function login(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'email' => ['required', 'string', 'email'],
            'password' => ['required', 'string'],
        ]);

        $user = User::query()
            ->where('email_index', BlindIndex::make($data['email']))
            ->first();

        if ($user === null || ! Hash::check($data['password'], $user->password)) {
            throw ValidationException::withMessages([
                'email' => ['These credentials do not match our records.'],
            ]);
        }

        if ($user->role === User::ROLE_ADMIN) {
            throw ValidationException::withMessages([
                'email' => ['Administrator accounts sign in through the admin dashboard.'],
            ]);
        }

        if (! $user->is_active) {
            throw ValidationException::withMessages([
                'email' => ['This account has been deactivated.'],
            ]);
        }

        // Volunteers need admin approval before they can sign in (M17.1).
        if ($user->role === User::ROLE_VOLUNTEER
            && ! ($user->verificationVolunteer?->isApproved() ?? false)) {
            throw ValidationException::withMessages([
                'email' => ['Your volunteer account is awaiting admin approval.'],
            ]);
        }

        // Collectors are signed to a sub-division first (M28.1).
        if ($user->role === User::ROLE_COLLECTOR
            && ! ($user->collectorAssignment?->is_active ?? false)) {
            throw ValidationException::withMessages([
                'email' => ['Your collector account is awaiting assignment to a sub-division.'],
            ]);
        }

        Auth::login($user, $request->boolean('remember'));
        $request->session()->regenerate();

        return redirect()->intended(route('dashboard'));
    }

    public function logout(Request $request): RedirectResponse
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('home');
    }

    private function home(User $user): RedirectResponse
    {
        if ($user->role === User::ROLE_ADMIN) {
            return redirect()->route('admin.donation.edit');
        }

        return redirect()->route('dashboard');
    }
}
