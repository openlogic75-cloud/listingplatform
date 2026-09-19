<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\RiderBaseOperation;
use App\Models\User;
use App\Rules\ActiveLocality;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

/**
 * Website base-of-operation setting (M15.2). Same rules as the API (M4.2):
 * one active district plus up to five active localities inside it. The
 * shared ActiveLocality rule is the single source of validation truth, so
 * web and app can never accept different areas.
 */
class DriverBaseController extends Controller
{
    public function update(Request $request): RedirectResponse
    {
        $user = $request->user();

        if ($user->role !== User::ROLE_DRIVER) {
            throw ValidationException::withMessages([
                'role' => ['Only drivers can set a base of operation.'],
            ]);
        }

        $data = $request->validate([
            'district_id' => [
                'required',
                'integer',
                Rule::exists('districts', 'id')->where('is_active', true),
            ],
            'locality_ids' => ['required', 'array', 'min:1', 'max:5'],
            'locality_ids.*' => [
                'integer',
                new ActiveLocality(fn () => $request->input('district_id')),
            ],
        ]);

        $base = RiderBaseOperation::query()->updateOrCreate(
            ['user_id' => $user->id],
            ['district_id' => $data['district_id']],
        );

        $base->localities()->sync($data['locality_ids']);

        return redirect()
            ->route('dashboard')
            ->with('status', 'Base of operation saved.');
    }
}
