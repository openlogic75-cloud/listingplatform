<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\District;
use App\Models\DriverAvailability;
use App\Models\Locality;
use App\Models\RiderBaseOperation;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Driver-availability view (M4.6, Q8): which drivers are online right now, per
 * locality. No map SDK, no geocoding, no ETA, no live GPS — just the honest
 * per-locality list/count derived from driver_availability (M4.3) and
 * rider_base_operations (M4.2). Public read: buyers never register.
 *
 * A locality answer is a locality with ≥1 online driver whose base covers it.
 * When no driver covers the locality, returns the district-level pool count
 * (mirrors the district fallback in JobMatchingService) so the app can say
 * "none here — nearest drivers in district available".
 */
class DriverAvailabilityOverviewController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $data = $request->validate([
            'district_id' => ['required', 'integer',
                'exists:districts,id',
            ],
            'locality_id' => ['nullable', 'integer',
                'exists:localities,id',
            ],
        ]);

        $districtId = (int) $data['district_id'];

        $query = Locality::query()
            ->where('district_id', $districtId)
            ->where('is_active', true)
            ->withCount(['driversAvailable as driver_count'])
            ->orderBy('name');

        // Optionally narrow the view to a single locality (listings/bookings).
        if (!empty($data['locality_id'])) {
            $query->where('id', $data['locality_id']);
        }

        /** @var \Illuminate\Database\Eloquent\Collection<int, Locality> $localities */
        $localities = $query->get();

        // Always report the district pool count so the app can render
        // "none in this locality — N drivers elsewhere in the district"
        // without a second request.
        $districtPoolCount = $this->onlineDriversInDistrict($districtId)->count();

        return response()->json([
            'data' => [
                'district_id' => $districtId,
                'as_of' => now()->toIso8601String(),
                'localities' => $localities
                    ->where('driver_count', '>', 0)
                    ->values()
                    ->map(fn (Locality $l) => [
                        'id' => $l->id,
                        'name' => $l->name,
                        'online_drivers' => $l->driver_count,
                    ]),
                'district_fallback_count' => $districtPoolCount,
            ],
        ]);
    }

    /**
     * Online drivers whose base is anywhere in the district. Mirrors the
     * JobMatchingService district fallback so the app can show "nearest
     * drivers in this district" when no locality is covered.
     *
     * @return \Illuminate\Database\Eloquent\Collection<int, User>
     */
    private function onlineDriversInDistrict(int $districtId): \Illuminate\Database\Eloquent\Collection
    {
        return User::query()
            ->where('role', User::ROLE_DRIVER)
            ->whereHas('driverAvailability', fn ($q) => $q->where('is_online', true))
            ->whereHas('riderBaseOperation', fn ($q) => $q->where('district_id', $districtId))
            ->get();
    }
}