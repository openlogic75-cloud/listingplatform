<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\BookingResource;
use App\Models\Booking;
use App\Models\Vendor;
use App\Services\BookingService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

/**
 * Guest booking endpoints (M3.1/M3.3). No account - contact name + phone
 * only, purpose-limited with a recorded consent. Guests never see other
 * people's bookings: lookup requires code AND the booking phone.
 */
class BookingController extends Controller
{
    public function __construct(private BookingService $bookings)
    {
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'vendor_id' => ['required', 'integer', 'exists:vendors,id'],
            'contact_name' => ['required', 'string', 'max:120'],
            'contact_phone' => ['required', 'string', 'max:20'],
            'items' => ['required', 'array', 'min:1', 'max:20'],
            'items.*.product_id' => ['required', 'integer'],
            'items.*.quantity' => ['required', 'integer', 'min:1', 'max:100000'],
            'is_reseller' => ['nullable', 'boolean'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ]);

        $vendor = Vendor::query()->findOrFail($data['vendor_id']);

        $booking = $this->bookings->createGuestBooking(
            $vendor,
            $data['contact_name'],
            $data['contact_phone'],
            $data['items'],
            (bool) ($data['is_reseller'] ?? false),
            $data['notes'] ?? null,
        );

        return response()->json([
            'data' => new BookingResource($booking->load(['items.product', 'vendor'])),
            'message' => 'Booking placed. The seller will contact you on the phone you provided.',
        ], 201);
    }
    public function lookup(Request $request): JsonResponse
    {
        $data = $request->validate([
            'code' => ['required', 'string', 'max:20'],
            'phone' => ['required', 'string', 'max:20'],
        ]);

        $booking = $this->bookings->lookupForGuest($data['code'], $data['phone']);

        if ($booking === null) {
            throw ValidationException::withMessages([
                'code' => ['No booking matches that code and phone number.'],
            ]);
        }

        return response()->json([
            'data' => new BookingResource($booking),
        ]);
    }

    public function cancel(Request $request): JsonResponse
    {
        $data = $request->validate([
            'code' => ['required', 'string', 'max:20'],
            'phone' => ['required', 'string', 'max:20'],
        ]);

        $booking = $this->bookings->lookupForGuest($data['code'], $data['phone']);

        if ($booking === null) {
            throw ValidationException::withMessages([
                'code' => ['No booking matches that code and phone number.'],
            ]);
        }

        $this->bookings->cancelByGuest($booking, $data['phone']);

        return response()->json([
            'data' => new BookingResource($booking->refresh()),
            'message' => 'Booking cancelled.',
        ]);
    }
}
