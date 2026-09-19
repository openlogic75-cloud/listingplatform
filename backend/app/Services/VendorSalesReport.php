<?php

namespace App\Services;

use App\Models\Booking;
use App\Models\Vendor;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

/**
 * Vendor sales reporting (M21.1). "Sales" are bookings the vendor marked
 * Completed — money fulfilled and settled directly between the parties. The
 * platform takes no commission and holds no money, so these are recorded
 * figures for the vendor's own bookkeeping, not a payment ledger.
 */
class VendorSalesReport
{
    /**
     * @return array<string, mixed>
     */
    public function forVendor(Vendor $vendor): array
    {
        $completed = Booking::query()
            ->where('vendor_id', $vendor->id)
            ->where('status', Booking::STATUS_COMPLETED)
            ->with('items')
            ->get();

        $now = now();

        return [
            'periods' => [
                $this->period('This month', $now->copy()->startOfMonth(), $completed, $now->format('F Y')),
                $this->period(
                    'This quarter',
                    $now->copy()->startOfQuarter(),
                    $completed,
                    'Q'.$now->quarter.' '.$now->year,
                ),
                $this->period('This year', $now->copy()->startOfYear(), $completed, (string) $now->year),
            ],
            'recent' => $completed
                ->sortByDesc(fn (Booking $booking) => $booking->completed_at?->timestamp ?? 0)
                ->take(20)
                ->map(fn (Booking $booking) => [
                    'code' => $booking->code,
                    'completed_at' => $booking->completed_at,
                    'value' => $this->value($booking),
                ])
                ->values()
                ->all(),
        ];
    }

    /**
     * @param  Collection<int, Booking>  $completed
     * @return array{label: string, count: int, total: float}
     */
    private function period(string $title, Carbon $start, Collection $completed, string $caption): array
    {
        $inPeriod = $completed->filter(
            fn (Booking $booking) => $booking->completed_at !== null
                && $booking->completed_at->greaterThanOrEqualTo($start),
        );

        return [
            'title' => $title,
            'caption' => $caption,
            'count' => $inPeriod->count(),
            'total' => round($inPeriod->sum(fn (Booking $booking) => $this->value($booking)), 2),
        ];
    }

    public function value(Booking $booking): float
    {
        return (float) $booking->items->sum(
            fn ($item) => (float) $item->unit_price_snapshot * (int) $item->quantity,
        );
    }
}
