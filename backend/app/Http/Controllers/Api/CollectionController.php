<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\District;
use App\Models\LogisticsJob;
use App\Models\Product;
use App\Services\JobMatchingService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

/**
 * Farm-produce collection jobs (M28.3). A vendor asks for their bulk farm
 * produce to be collected from its sub-division and brought to a hub district.
 * The job goes to that sub-division's collector (M28.1) — collectors do not do
 * delivery or errands. The fee is paid directly between them; the platform
 * takes nothing.
 */
class CollectionController extends Controller
{
    public function __construct(private JobMatchingService $matcher = new JobMatchingService) {}

    /**
     * Vendor: request collection of a farm-produce listing to a hub.
     */
    public function store(Request $request): JsonResponse
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

        // Hand it to the sub-division's signed collector, if there is one.
        $collector = $this->matcher->collectorFor($product->locality_id);

        if ($collector !== null) {
            $job->update([
                'collector_id' => $collector->id,
                'status' => LogisticsJob::STATUS_ASSIGNED,
                'assigned_at' => now(),
            ]);
        }

        return response()->json([
            'data' => $this->payload($job->fresh(['locality.district', 'destinationDistrict'])),
            'assigned' => $job->collector_id !== null,
        ], 201);
    }

    /**
     * Collector: collection jobs in my sub-division.
     */
    public function index(Request $request): JsonResponse
    {
        $assignment = $request->user()->collectorAssignment;

        if (! $assignment?->is_active) {
            return response()->json(['data' => []]);
        }

        $jobs = LogisticsJob::query()
            ->where('type', LogisticsJob::TYPE_COLLECT_PRODUCE)
            ->where('locality_id', $assignment->locality_id)
            ->whereIn('status', [
                LogisticsJob::STATUS_REQUESTED,
                LogisticsJob::STATUS_ASSIGNED,
                LogisticsJob::STATUS_ACCEPTED,
                LogisticsJob::STATUS_IN_PROGRESS,
            ])
            ->with(['locality.district', 'destinationDistrict'])
            ->latest()
            ->limit(50)
            ->get();

        return response()->json(['data' => $jobs->map(fn (LogisticsJob $j) => $this->payload($j))]);
    }

    /**
     * Collector: accept a collection in my sub-division.
     */
    public function accept(Request $request, LogisticsJob $job): JsonResponse
    {
        $this->ensureCollectorJob($request, $job);

        if (! in_array($job->status, [LogisticsJob::STATUS_REQUESTED, LogisticsJob::STATUS_ASSIGNED], true)) {
            throw ValidationException::withMessages([
                'status' => ["A {$job->status} collection cannot be accepted."],
            ]);
        }

        $job->update([
            'collector_id' => $request->user()->id,
            'status' => LogisticsJob::STATUS_ACCEPTED,
            'assigned_at' => $job->assigned_at ?? now(),
        ]);

        return response()->json(['data' => $this->payload($job->fresh(['locality', 'destinationDistrict']))]);
    }

    /**
     * Collector: move an accepted collection along (collected → dropped).
     */
    public function updateStatus(Request $request, LogisticsJob $job): JsonResponse
    {
        $this->ensureCollectorJob($request, $job);

        $data = $request->validate([
            'status' => ['required', 'string', 'in:in_progress,completed,cancelled'],
        ]);

        if ($job->collector_id !== $request->user()->id) {
            throw ValidationException::withMessages([
                'collector' => ['This collection is not assigned to you.'],
            ]);
        }

        $job->update([
            'status' => $data['status'],
            'completed_at' => $data['status'] === LogisticsJob::STATUS_COMPLETED ? now() : $job->completed_at,
        ]);

        return response()->json(['data' => $this->payload($job->fresh(['locality', 'destinationDistrict']))]);
    }

    private function ensureCollectorJob(Request $request, LogisticsJob $job): void
    {
        $assignment = $request->user()->collectorAssignment;

        if ($job->type !== LogisticsJob::TYPE_COLLECT_PRODUCE
            || ! $assignment?->is_active
            || $job->locality_id !== $assignment->locality_id) {
            abort(403);
        }
    }

    /**
     * @return array<string, mixed>
     */
    private function payload(LogisticsJob $job): array
    {
        return [
            'id' => $job->id,
            'status' => $job->status,
            'type' => $job->type,
            'fee_inr' => $job->fee_inr,
            'address' => $job->address,
            'locality' => $job->locality?->name,
            'district' => $job->locality?->district?->name,
            'destination' => $job->destinationDistrict?->name,
            'assigned_to_me' => $job->collector_id !== null,
            'created_at' => $job->created_at?->toIso8601String(),
        ];
    }
}
