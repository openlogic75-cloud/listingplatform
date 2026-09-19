<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Rules\UniquePhone;
use App\Support\BlindIndex;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

/**
 * Website vendor shop profile (M12.3). Same fields as the app's M9.4 screen
 * and the API's PUT /profile: name, phone, shop display name and description
 * (category and district are set at registration).
 */
class VendorProfileController extends Controller
{
    public function update(Request $request): RedirectResponse
    {
        $user = $request->user();

        if ($user->role !== User::ROLE_VENDOR) {
            throw ValidationException::withMessages([
                'role' => ['Only vendors can edit this profile.'],
            ]);
        }

        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'phone' => ['nullable', 'string', 'max:20', new UniquePhone($user)],
            'display_name' => ['required', 'string', 'max:120'],
            'vendor_description' => ['nullable', 'string', 'max:2000'],
        ]);

        $user->name = $data['name'];
        $user->phone = $data['phone'] ?? null;
        $user->phone_index = ($data['phone'] ?? null) !== null && $data['phone'] !== ''
            ? BlindIndex::make($data['phone'])
            : null;
        $user->save();

        $vendor = $user->vendor;

        if ($vendor !== null) {
            $vendor->fill([
                'display_name' => $data['display_name'],
                'description' => $data['vendor_description'] ?? null,
            ])->save();
        }

        return redirect()
            ->route('dashboard')
            ->with('status', 'Shop profile saved.');
    }
}
