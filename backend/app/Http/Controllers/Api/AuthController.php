<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Support\BlindIndex;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    /**
     * Login for registered roles only (vendor, driver, collector, worker,
     * volunteer, admin). Buyers browse as guests and have no account.
     */
    public function login(Request $request): JsonResponse
    {
        $data = $request->validate([
            'email' => ['required', 'string', 'email'],
            'password' => ['required', 'string'],
            'device_name' => ['nullable', 'string', 'max:120'],
        ]);

        $user = User::query()
            ->where('email_index', BlindIndex::make($data['email']))
            ->first();

        if ($user === null || ! Hash::check($data['password'], $user->password)) {
            throw ValidationException::withMessages([
                'email' => ['These credentials do not match our records.'],
            ]);
        }

        if (! $user->is_active) {
            return response()->json([
                'message' => 'This account has been deactivated. Contact support for data options.',
            ], 403);
        }

        // Volunteers need admin approval before they can use the app (M17.1).
        if ($user->role === User::ROLE_VOLUNTEER
            && ! ($user->verificationVolunteer?->isApproved() ?? false)) {
            return response()->json([
                'message' => 'Your volunteer account is awaiting admin approval.',
            ], 403);
        }

        // Collectors are signed to a sub-division first (M28.1).
        if ($user->role === User::ROLE_COLLECTOR
            && ! ($user->collectorAssignment?->is_active ?? false)) {
            return response()->json([
                'message' => 'Your collector account is awaiting assignment to a sub-division.',
            ], 403);
        }

        $token = $user->createToken($data['device_name'] ?? 'app')->plainTextToken;

        return response()->json([
            'token' => $token,
            'user' => $user->profile(),
        ]);
    }

    public function me(Request $request): JsonResponse
    {
        return response()->json([
            'user' => $request->user()->profile(),
        ]);
    }

    public function logout(Request $request): JsonResponse
    {
        $request->user()->currentAccessToken()?->delete();

        return response()->json([
            'message' => 'Logged out.',
        ]);
    }
}
