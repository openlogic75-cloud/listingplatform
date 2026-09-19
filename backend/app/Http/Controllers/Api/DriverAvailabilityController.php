<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\DriverAvailability;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

/**
 * Online/offline availability toggle (M4.3). Only online drivers enter the
 * job-matching pools. last_online_at updates on every toggle so the app can
 * show "away since" for recently-offline drivers.
 */
class DriverAvailabilityController extends Controller
{
    public function show(Request $request): JsonResponse
    {
        $driver = $request->user();

        if ($driver->role !== User::ROLE_DRIVER) {
            return response()->json(['data' => null]);
        }

        $availability = $driver->driverAvailability
            ?? DriverAvailability::query()->create(['user_id' => $driver->id]);

        return response()->json([
            'data' => [
                'is_online' => $availability->is_online,
                'last_online_at' => $availability->last_online_at,
            ],
        ]);
    }

    public function update(Request $request): JsonResponse
    {
        $driver = $request->user();

        if ($driver->role !== User::ROLE_DRIVER) {
            throw ValidationException::withMessages([
                'role' => ['Only drivers can toggle availability.'],
            ]);
        }

        $data = $request->validate([
            'is_online' => ['required', 'boolean'],
        ]);

        $availability = DriverAvailability::query()->updateOrCreate(
            ['user_id' => $driver->id],
            [
                'is_online' => $data['is_online'],
                'last_online_at' => $data['is_online'] ? null : now(),
            ],
        );

        return response()->json([
            'data' => [
                'is_online' => $availability->is_online,
                'last_online_at' => $availability->last_online_at,
            ],
        ]);
    }
}
