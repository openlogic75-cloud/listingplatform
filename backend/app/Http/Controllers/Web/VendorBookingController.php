<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Services\BookingService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

/**
 * Website vendor bookings (M12.4). Lists the vendor's bookings and advances
 * them through the same BookingService lifecycle the app uses — status rules
 * live in one place, never in the controllers. Ownership is enforced by
 * BookingPolicy.
 */
class VendorBookingController extends Controller
{
    public function __construct(private BookingService $bookings) {}

    public function index(Request $request): View|RedirectResponse
    {
        $vendor = $request->user()->vendor;

        if ($vendor === null) {
            return redirect()->route('dashboard');
        }

        return view('dashboard.bookings', [
            'bookings' => $vendor->bookings()
                ->with(['items.product'])
                ->latest()
                ->limit(100)
                ->get(),
            'transitions' => BookingService::TRANSITIONS,
        ]);
    }

    public function updateStatus(Request $request, Booking $booking): RedirectResponse
    {
        $this->authorize('transition', $booking);

        $data = $request->validate([
            'status' => [
                'required',
                'string',
                Rule::in(array_keys(BookingService::TRANSITIONS)),
            ],
        ]);

        $this->bookings->changeStatus($booking, $data['status']);

        return redirect()
            ->route('dashboard.bookings')
            ->with('status', "Booking {$booking->code} updated.");
    }
}
