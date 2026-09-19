<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Errand;
use App\Models\User;
use App\Rules\ActiveLocality;
use App\Services\JobMatchingService;
use App\Support\BlindIndex;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

/**
 * Errands (M4.5): ride-style pickup/drop tasks for any user. Guests can
 * create errands with just a contact name + phone (encrypted, consented).
 * Reuses the same matcher as logistics jobs.
 */
class ErrandController extends Controller
{
    public function __construct(private JobMatchingService $matcher = new JobMatchingService) {}

    /**
     * Guest or registered user creates an errand.
     */
    public function store(Request $request): JsonResponse
    {
        $user = $request->user();
        $isGuest = $user === null;

        $data = $request->validate([
            'contact_name' => $isGuest ? ['required', 'string', 'max:120'] : ['nullable', 'string'],
            'contact_phone' => $isGuest ? ['required', 'string', 'max:20'] : ['nullable', 'string'],
            'description' => ['required', 'string', 'max:1000'],
            'pickup_district_id' => [
                'required',
                'integer',
                Rule::exists('districts', 'id')->where('is_active', true),
            ],
            'pickup_locality_id' => [
                'required',
                'integer',
                new ActiveLocality(fn () => $request->input('pickup_district_id')),
            ],
            'pickup_address' => ['nullable', 'string', 'max:500'],
            'drop_district_id' => [
                'required',
                'integer',
                Rule::exists('districts', 'id')->where('is_active', true),
            ],
            'drop_locality_id' => [
                'required',
                'integer',
                new ActiveLocality(fn () => $request->input('drop_district_id')),
            ],
            'drop_address' => ['nullable', 'string', 'max:500'],
        ]);

        $contactName = $isGuest ? $data['contact_name'] : $user->name;
        $contactPhone = $isGuest ? $data['contact_phone'] : ($user->phone ?? '');

        $code = $this->generateCode();

        $errand = Errand::query()->create([
            'code' => $code,
            'customer_id' => $user?->id,
            'contact_name' => $contactName,
            'contact_phone' => $contactPhone,
            'contact_phone_index' => $contactPhone ? BlindIndex::make($contactPhone) : null,
            'description' => $data['description'],
            'pickup_district_id' => $data['pickup_district_id'],
            'pickup_locality_id' => $data['pickup_locality_id'],
            'pickup_address' => $data['pickup_address'] ?? null,
            'drop_district_id' => $data['drop_district_id'],
            'drop_locality_id' => $data['drop_locality_id'],
            'drop_address' => $data['drop_address'] ?? null,
            'status' => Errand::STATUS_REQUESTED,
        ]);

        $this->matcher->assign($errand);

        return response()->json([
            'data' => $errand->fresh(),
            'assigned_driver' => $errand->driver_id !== null,
            'code' => $code,
        ], 201);
    }

    /**
     * List errands in the caller's base localities (drivers only).
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

        $errands = Errand::query()
            ->whereIn('pickup_locality_id', $localityIds)
            ->where('status', Errand::STATUS_REQUESTED)
            ->latest()
            ->get();

        return response()->json([
            'data' => $errands->map(function (Errand $errand) {
                return [
                    'id' => $errand->id,
                    'code' => $errand->code,
                    'description' => $errand->description,
                    'pickup_address' => $errand->pickup_address,
                    'drop_address' => $errand->drop_address,
                    'status' => $errand->status,
                    'created_at' => $errand->created_at,
                ];
            }),
        ]);
    }

    /**
     * Driver accepts an errand.
     */
    public function accept(Request $request, Errand $errand): JsonResponse
    {
        $driver = $request->user();

        if ($driver->role !== User::ROLE_DRIVER) {
            throw ValidationException::withMessages([
                'role' => ['Only drivers can accept errands.'],
            ]);
        }

        if (in_array($errand->status, [Errand::STATUS_COMPLETED, Errand::STATUS_CANCELLED], true)) {
            throw ValidationException::withMessages([
                'status' => ["Cannot accept a {$errand->status} errand."],
            ]);
        }

        $errand->update([
            'driver_id' => $driver->id,
            'status' => Errand::STATUS_ACCEPTED,
        ]);

        return response()->json([
            'data' => $errand->fresh(),
        ]);
    }

    /**
     * Driver progresses errand: in_progress -> completed.
     */
    public function updateStatus(Request $request, Errand $errand): JsonResponse
    {
        $driver = $request->user();

        if ($driver->role !== User::ROLE_DRIVER) {
            throw ValidationException::withMessages([
                'role' => ['Only drivers can update errand status.'],
            ]);
        }

        $data = $request->validate([
            'status' => ['required', Rule::in([Errand::STATUS_IN_PROGRESS, Errand::STATUS_COMPLETED])],
        ]);

        if ($errand->driver_id !== $driver->id) {
            throw ValidationException::withMessages([
                'id' => ['This errand is not assigned to you.'],
            ]);
        }

        $errand->update(['status' => $data['status']]);

        return response()->json([
            'data' => $errand->fresh(),
        ]);
    }

    /**
     * Guest lookup by code (no login required).
     */
    public function lookup(Request $request): JsonResponse
    {
        $code = $request->validate([
            'code' => ['required', 'string', 'max:20'],
            'phone' => ['required', 'string', 'max:20'],
        ]);

        $errand = Errand::query()
            ->where('code', $code['code'])
            ->where('contact_phone_index', BlindIndex::make($code['phone']))
            ->first();

        if ($errand === null) {
            throw ValidationException::withMessages([
                'code' => ['No errand matches that code and phone number.'],
            ]);
        }

        return response()->json([
            'data' => [
                'id' => $errand->id,
                'code' => $errand->code,
                'status' => $errand->status,
                'pickup_address' => $errand->pickup_address,
                'drop_address' => $errand->drop_address,
                'created_at' => $errand->created_at,
            ],
        ]);
    }

    private function generateCode(): string
    {
        do {
            $code = 'ER-'.strtoupper(Str::random(6));
        } while (Errand::query()->where('code', $code)->exists());

        return $code;
    }
}
