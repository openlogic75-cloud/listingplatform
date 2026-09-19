<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Models\LogisticsJob;
use App\Models\User;
use App\Rules\ActiveLocality;
use App\Services\JobMatchingService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

/**
 * Logistics jobs (M4.4): vendors request pickup/delivery for a booking,
 * drivers see incoming jobs and accept. Matching handled by
 * JobMatchingService (locality-match + district fallback).
 */
class LogisticsController extends Controller
{
    public function __construct(private JobMatchingService $matcher = new JobMatchingService) {}

    /**
     * Vendor: request pickup or delivery for a booking.
     */
    public function store(Request $request): JsonResponse
    {
        $vendor = $request->user()->vendor;

        if ($vendor === null) {
            throw ValidationException::withMessages([
                'role' => ['Only vendors can create logistics jobs.'],
            ]);
        }

        $data = $request->validate([
            'booking_id' => ['required', 'integer', 'exists:bookings,id'],
            'type' => ['required', Rule::in([LogisticsJob::TYPE_PICKUP, LogisticsJob::TYPE_DELIVERY])],
            'district_id' => [
                'required',
                'integer',
                Rule::exists('districts', 'id')->where('is_active', true),
            ],
            'locality_id' => [
                'required',
                'integer',
                new ActiveLocality(fn () => $request->input('district_id')),
            ],
            'address' => ['nullable', 'string', 'max:500'],
        ]);

        $booking = Booking::query()->findOrFail($data['booking_id']);

        if ($booking->vendor_id !== $vendor->id) {
            throw ValidationException::withMessages([
                'booking_id' => ['This booking does not belong to you.'],
            ]);
        }

        $job = LogisticsJob::query()->create([
            'booking_id' => $booking->id,
            'vendor_id' => $vendor->id,
            'type' => $data['type'],
            'district_id' => $data['district_id'],
            'locality_id' => $data['locality_id'],
            'address' => $data['address'] ?? null,
            'status' => LogisticsJob::STATUS_REQUESTED,
        ]);

        // Attempt matching immediately (synchronous, Hostinger-compatible).
        $this->matcher->assign($job);

        return response()->json([
            'data' => $job->load('booking'),
            'assigned_driver' => $job->driver_id !== null,
        ], 201);
    }

    /**
     * Driver: list jobs in my base localities that are awaiting pickup.
     */
    public function index(Request $request): JsonResponse
    {
        $driver = $request->user();

        if ($driver->role !== User::ROLE_DRIVER) {
            return response()->json(['data' => []]);
        }

        $base = $driver->riderBaseOperation;

        if ($base === null) {
            return response()->json(['data' => []]);
        }

        $localityIds = $base->localities->modelKeys();

        $jobs = LogisticsJob::query()
            ->whereIn('locality_id', $localityIds)
            ->where('status', LogisticsJob::STATUS_REQUESTED)
            ->with('booking.vendor')
            ->latest()
            ->get();

        return response()->json([
            'data' => $jobs->map(function (LogisticsJob $job) {
                return [
                    'id' => $job->id,
                    'type' => $job->type,
                    'status' => $job->status,
                    'address' => $job->address,
                    'booking_code' => $job->booking?->code,
                    'vendor_display_name' => $job->booking?->vendor?->display_name,
                    'created_at' => $job->created_at,
                ];
            }),
        ]);
    }

    /**
     * Driver: accept a job.
     */
    public function accept(Request $request, LogisticsJob $job): JsonResponse
    {
        $driver = $request->user();

        if ($driver->role !== User::ROLE_DRIVER) {
            throw ValidationException::withMessages([
                'role' => ['Only drivers can accept jobs.'],
            ]);
        }

        if ($job->status !== LogisticsJob::STATUS_REQUESTED && $job->status !== LogisticsJob::STATUS_ASSIGNED) {
            throw ValidationException::withMessages([
                'status' => ["A {$job->status} job cannot be accepted."],
            ]);
        }

        $job->update([
            'driver_id' => $driver->id,
            'status' => LogisticsJob::STATUS_ACCEPTED,
        ]);

        return response()->json([
            'data' => $job->fresh(),
        ]);
    }

    /**
     * Driver: mark job as in_progress or completed.
     */
    public function updateStatus(Request $request, LogisticsJob $job): JsonResponse
    {
        $driver = $request->user();

        if ($driver->role !== User::ROLE_DRIVER) {
            throw ValidationException::withMessages([
                'role' => ['Only drivers can update job status.'],
            ]);
        }

        $data = $request->validate([
            'status' => ['required', 'string', Rule::in([
                LogisticsJob::STATUS_IN_PROGRESS,
                LogisticsJob::STATUS_COMPLETED,
            ])],
        ]);

        // Only the assigned driver can progress the job.
        if ($job->driver_id !== $driver->id) {
            throw ValidationException::withMessages([
                'id' => ['This job is not assigned to you.'],
            ]);
        }

        $job->update([
            'status' => $data['status'],
            'completed_at' => $data['status'] === LogisticsJob::STATUS_COMPLETED ? now() : $job->completed_at,
        ]);

        return response()->json([
            'data' => $job->fresh(),
        ]);
    }
}
