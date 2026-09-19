<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\WorkerProfile;
use App\Rules\UniquePhone;
use App\Support\BlindIndex;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

/**
 * Website skilled-worker profile (M15.3). Skilled workers could read their
 * profile on the site but had to edit it in the app; this closes that gap.
 * Fields match the API's PUT /profile (name, phone, services).
 */
class WorkerProfileController extends Controller
{
    public function update(Request $request): RedirectResponse
    {
        $user = $request->user();

        if ($user->role !== User::ROLE_SKILLED_WORKER) {
            throw ValidationException::withMessages([
                'role' => ['Only skilled workers can edit this profile.'],
            ]);
        }

        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'phone' => [
                'nullable',
                'string',
                'max:20',
                new UniquePhone($user),
            ],
            'services' => ['nullable', 'string', 'max:2000'],
            'skill_category_ids' => ['nullable', 'array'],
            'skill_category_ids.*' => [
                'integer',
                Rule::exists('skill_categories', 'id')->where('is_active', true),
            ],
        ]);

        $user->name = $data['name'];
        $user->phone = $data['phone'] ?? null;
        $user->phone_index = ($data['phone'] ?? null) !== null && $data['phone'] !== ''
            ? BlindIndex::make($data['phone'])
            : null;
        $user->save();

        $profile = WorkerProfile::query()->updateOrCreate(
            ['user_id' => $user->id],
            ['services' => $data['services'] ?? null],
        );

        $profile->skillCategories()->sync($data['skill_category_ids'] ?? []);

        return redirect()
            ->route('dashboard')
            ->with('status', 'Profile saved.');
    }
}
