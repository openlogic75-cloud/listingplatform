<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\BookingResource;
use App\Models\Booking;
use App\Services\BookingService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/**
 * Vendor-facing bookings (M3.2): list own bookings, move them through the
 * lifecycle. Transitions validate in BookingService; ownership in policy.
 */
class VendorBookingController extends Controller
{
    public function __construct(private BookingService $bookings)
    {
    }

    public function index(Request $request): JsonResponse
    {
        $data = $request->validate([
            'status' => ['nullable', 'string', 'in:'.implode(',', Booking::STATUSES)],
        ]);

        $vendor = $request->user()->vendor;

        if ($vendor === null) {
            return response()->json(['data' => []]);
        }

        $bookings = $vendor->bookings()
            ->with(['items.product', 'vendor'])
            ->when(isset($data['status']), fn ($q) => $q->where('status', $data['status']))
            ->latest()
            ->limit(100)
            ->get();

        return response()->json([
            'data' => BookingResource::collection($bookings),
        ]);
    }

    public function updateStatus(Request $request, Booking $booking): JsonResponse
    {
        $this->authorize('transition', $booking);

        $data = $request->validate([
            'status' => [
                'required',
                'string',
                Rule::in(array_keys(BookingService::TRANSITIONS)),
            ],
        ]);

        $booking = $this->bookings->changeStatus($booking, $data['status']);

        return response()->json([
            'data' => new BookingResource($booking->load(['items.product', 'vendor'])),
        ]);
    }
}
