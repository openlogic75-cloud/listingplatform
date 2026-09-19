<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\DeviceToken;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

/**
 * Device push-token management (M8.1). The app registers its FCM token here;
 * the NotificationService uses it to deliver a notification synchronously on
 * shared hosting (no queue workers).
 */
class DeviceTokenController extends Controller
{
    public function store(Request $request): JsonResponse
    {
        $user = $request->user();

        if ($user === null) {
            return response()->json(['message' => 'Authentication required.'], 401);
        }

        $data = $request->validate([
            'token' => ['required', 'string', 'max:255'],
            'platform' => ['nullable', 'string', 'in:'.implode(',', [
                DeviceToken::PLATFORM_ANDROID,
                DeviceToken::PLATFORM_IOS,
                DeviceToken::PLATFORM_WEB,
            ])],
        ]);

        $deviceToken = DeviceToken::query()->updateOrCreate(
            ['token' => $data['token']],
            [
                'user_id' => $user->id,
                'platform' => $data['platform'] ?? DeviceToken::PLATFORM_ANDROID,
            ],
        );

        return response()->json([
            'data' => [
                'id' => $deviceToken->id,
                'token' => $deviceToken->token,
                'platform' => $deviceToken->platform,
            ],
        ], 201);
    }

    /**
     * Remove a device token (logout / app uninstall).
     */
    public function destroy(Request $request, string $token): JsonResponse
    {
        $user = $request->user();

        if ($user === null) {
            return response()->json(['message' => 'Authentication required.'], 401);
        }

        $deviceToken = DeviceToken::query()
            ->where('user_id', $user->id)
            ->where('token', $token)
            ->first();

        if ($deviceToken === null) {
            throw ValidationException::withMessages([
                'token' => ['This device token is not registered to you.'],
            ]);
        }

        $deviceToken->delete();

        return response()->json([
            'message' => 'Device token removed.',
        ]);
    }
}