<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\LogisticsJob;
use App\Models\Product;
use App\Services\JobMatchingService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

/**
 * Web-side farm-produce collection request (M28.5), the website counterpart of
 * the app's collection request. The fee is paid directly to the collector.
 */
class CollectionController extends Controller
{
    public function __construct(private JobMatchingService $matcher = new JobMatchingService) {}

    public function store(Request $request): RedirectResponse
    {
        $vendor = $request->user()->vendor;

        if ($vendor === null) {
            throw ValidationException::withMessages([
                'role' => ['Only vendors can request a collection.'],
            ]);
        }

        $data = $request->validate([
            'product_id' => ['required', 'integer', 'exists:products,id'],
            'destination_district_id' => [
                'required', 'integer',
                Rule::exists('districts', 'id')->where('is_hub', true),
            ],
            'fee_inr' => ['nullable', 'numeric', 'min:0', 'max:1000000'],
            'address' => ['nullable', 'string', 'max:500'],
        ]);

        $product = Product::query()->findOrFail($data['product_id']);

        if ($product->vendor_id !== $vendor->id) {
            throw ValidationException::withMessages([
                'product_id' => ['That listing does not belong to you.'],
            ]);
        }

        if ($product->category !== Product::CATEGORY_FARM_RESELLER) {
            throw ValidationException::withMessages([
                'product_id' => ['Only farm-produce (reseller) listings can be collected.'],
            ]);
        }

        if ($product->district_id === null || $product->locality_id === null) {
            throw ValidationException::withMessages([
                'product_id' => ['Set the listing area before requesting a collection.'],
            ]);
        }

        $job = LogisticsJob::query()->create([
            'vendor_id' => $vendor->id,
            'type' => LogisticsJob::TYPE_COLLECT_PRODUCE,
            'district_id' => $product->district_id,
            'locality_id' => $product->locality_id,
            'drop_district_id' => $data['destination_district_id'],
            'fee_inr' => $data['fee_inr'] ?? null,
            'address' => $data['address'] ?? null,
            'status' => LogisticsJob::STATUS_REQUESTED,
        ]);

        $collector = $this->matcher->collectorFor($product->locality_id);

        if ($collector !== null) {
            $job->update([
                'collector_id' => $collector->id,
                'status' => LogisticsJob::STATUS_ASSIGNED,
                'assigned_at' => now(),
            ]);
        }

        return redirect()->route('dashboard')->with('status', 'Collection requested.');
    }
}
