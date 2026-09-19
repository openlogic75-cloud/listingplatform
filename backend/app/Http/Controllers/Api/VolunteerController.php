<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Media;
use App\Models\User;
use App\Models\Verification;
use App\Models\VerificationVolunteer;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

/**
 * Volunteer profile + visit queue (M5.1). Volunteers manage their availability
 * and browse their assigned verifications.
 */
class VolunteerController extends Controller
{
    public function show(Request $request): JsonResponse
    {
        $volunteer = $request->user()?->verificationVolunteer;

        if ($volunteer === null) {
            return response()->json(['data' => null]);
        }

        return response()->json([
            'data' => [
                'id' => $volunteer->id,
                'availability' => $volunteer->availability,
                'tada_notes' => $volunteer->tada_notes,
                'photo_path' => $volunteer->photo_path,
            ],
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $user = $request->user();

        if ($user->role !== User::ROLE_VOLUNTEER) {
            throw ValidationException::withMessages([
                'role' => ['Only volunteers can create a volunteer profile.'],
            ]);
        }

        $data = $request->validate([
            'availability' => ['nullable', 'string', 'max:1000'],
            'tada_notes' => ['nullable', 'string', 'max:1000'],
            'photo_path' => $this->photoRules($request),
        ]);

        $volunteer = VerificationVolunteer::query()->updateOrCreate(
            ['user_id' => $user->id],
            [
                'availability' => $data['availability'] ?? null,
                'tada_notes' => $data['tada_notes'] ?? null,
                'photo_path' => $data['photo_path'] ?? null,
            ]
        );

        return response()->json([
            'data' => [
                'id' => $volunteer->id,
                'availability' => $volunteer->availability,
                'tada_notes' => $volunteer->tada_notes,
                'photo_path' => $volunteer->photo_path,
            ],
        ], 201);
    }

    public function update(Request $request): JsonResponse
    {
        $volunteer = $request->user()?->verificationVolunteer;

        if ($volunteer === null) {
            throw ValidationException::withMessages([
                'profile' => ['Create a volunteer profile first.'],
            ]);
        }

        $data = $request->validate([
            'availability' => ['nullable', 'string', 'max:1000'],
            'tada_notes' => ['nullable', 'string', 'max:1000'],
            'photo_path' => $this->photoRules($request),
        ]);

        $volunteer->update([
            'availability' => $data['availability'] ?? $volunteer->availability,
            'tada_notes' => $data['tada_notes'] ?? $volunteer->tada_notes,
            'photo_path' => $data['photo_path'] ?? $volunteer->photo_path,
        ]);

        return response()->json([
            'data' => [
                'id' => $volunteer->id,
                'availability' => $volunteer->availability,
                'tada_notes' => $volunteer->tada_notes,
                'photo_path' => $volunteer->photo_path,
            ],
        ]);
    }

    /**
     * The volunteer's own photo must reference media they uploaded (M2.3).
     *
     * @return array<int, mixed>
     */
    private function photoRules(Request $request): array
    {
        return [
            'nullable',
            'string',
            'max:255',
            function (string $attribute, mixed $value, \Closure $fail) use ($request): void {
                if ($value === null || $value === '') {
                    return;
                }

                $owns = Media::query()
                    ->where('uploaded_by', $request->user()->id)
                    ->where('path', $value)
                    ->exists();

                if (! $owns) {
                    $fail('That photo does not belong to your uploads.');
                }
            },
        ];
    }

    /**
     * Visit queue: list verifications assigned to this volunteer.
     */
    public function queue(Request $request): JsonResponse
    {
        $volunteer = $request->user()?->verificationVolunteer;

        if ($volunteer === null) {
            return response()->json(['data' => []]);
        }

        $verifications = Verification::query()
            ->where('volunteer_id', $volunteer->id)
            ->with('subject')
            ->latest()
            ->limit(50)
            ->get();

        return response()->json([
            'data' => $verifications->map(fn ($v) => [
                'id' => $v->id,
                'subject_type' => $v->subject_type,
                'subject_id' => $v->subject_id,
                'status' => $v->status,
                'notes' => $v->notes,
                'created_at' => $v->created_at,
            ]),
        ]);
    }

    /**
     * Available verifications awaiting a volunteer.
     */
    public function available(Request $request): JsonResponse
    {
        $volunteer = $request->user()?->verificationVolunteer;

        if ($volunteer === null) {
            throw ValidationException::withMessages([
                'profile' => ['Create a volunteer profile first.'],
            ]);
        }

        // Verifications in draft with no volunteer assigned yet.
        $verifications = Verification::query()
            ->where('status', Verification::STATUS_DRAFT)
            ->whereNull('volunteer_id')
            ->with('subject')
            ->latest()
            ->limit(20)
            ->get();

        return response()->json([
            'data' => $verifications->map(fn ($v) => [
                'id' => $v->id,
                'subject_type' => $v->subject_type,
                'subject_id' => $v->subject_id,
                'notes' => $v->notes,
                'created_at' => $v->created_at,
            ]),
        ]);
    }
}
