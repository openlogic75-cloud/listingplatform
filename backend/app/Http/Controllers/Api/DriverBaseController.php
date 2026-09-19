<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\RiderBaseOperation;
use App\Models\User;
use App\Rules\ActiveLocality;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

/**
 * Driver base-of-operation (M4.2): one district + up to five localities.
 * Server-enforced max-five rule. Only online drivers enter the matching
 * pools (see DriverAvailabilityController and JobMatchingService). Bases
 * can only target active service areas (M9.1).
 */
class DriverBaseController extends Controller
{
    public function show(Request $request): JsonResponse
    {
        $driver = $request->user();

        if ($driver->role !== User::ROLE_DRIVER) {
            return response()->json(['data' => null]);
        }

        $base = $driver->riderBaseOperation?->load('localities.district');

        return response()->json([
            'data' => $base ? [
                'district_id' => $base->district_id,
                'localities' => $base->localities->pluck('id'),
                'locality_count' => $base->localities->count(),
            ] : null,
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $this->ensureDriver($request);

        $data = $request->validate([
            'district_id' => [
                'required',
                'integer',
                Rule::exists('districts', 'id')->where('is_active', true),
            ],
            'locality_ids' => ['required', 'array', 'min:1', 'max:5'],
            'locality_ids.*' => [
                'integer',
                new ActiveLocality(fn () => $request->input('district_id')),
            ],
        ]);

        $driver = $request->user();

        $base = RiderBaseOperation::query()->updateOrCreate(
            ['user_id' => $driver->id],
            ['district_id' => $data['district_id']],
        );

        $base->localities()->sync($data['locality_ids']);

        return response()->json([
            'data' => [
                'district_id' => $base->district_id,
                'localities' => $base->localities->modelKeys(),
                'locality_count' => $base->localities->count(),
            ],
        ], 201);
    }

    public function update(Request $request): JsonResponse
    {
        $this->ensureDriver($request);

        $driver = $request->user();
        $base = $driver->riderBaseOperation;

        if ($base === null) {
            throw ValidationException::withMessages([
                'base' => ['Set your district first, then update localities.'],
            ]);
        }

        // Re-validates that the new localities belong to the same district
        // and stay inside active service areas (M9.1).
        $data = $request->validate([
            'locality_ids' => ['required', 'array', 'min:1', 'max:5'],
            'locality_ids.*' => [
                'integer',
                new ActiveLocality(fn () => $base->district_id),
            ],
        ]);

        $base->localities()->sync($data['locality_ids']);

        return response()->json([
            'data' => [
                'district_id' => $base->district_id,
                'localities' => $base->localities->modelKeys(),
                'locality_count' => $base->localities->count(),
            ],
        ]);
    }

    private function ensureDriver(Request $request): void
    {
        if ($request->user()->role !== User::ROLE_DRIVER) {
            throw ValidationException::withMessages([
                'role' => ['Only drivers can set a base of operation.'],
            ]);
        }
    }
}
