<?php

namespace App\Http\Controllers\Web\Admin;

use App\Http\Controllers\Controller;
use App\Models\CollectorAssignment;
use App\Models\District;
use App\Models\Locality;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

/**
 * Collector signing (M28.1). A locality is a sub-division; the admin signs one
 * collector to each. Collectors only collect farm produce to a hub district —
 * they are not part of the delivery or errand flow.
 */
class CollectorController extends Controller
{
    public function index(): View
    {
        return view('admin.collectors.index', [
            'collectors' => User::query()
                ->where('role', User::ROLE_COLLECTOR)
                ->with(['collectorAssignment.locality.district'])
                ->orderBy('name')
                ->get(),
            'localities' => Locality::query()
                ->with('district')
                ->orderBy('name')
                ->get(),
            'hubs' => District::query()->where('is_hub', true)->orderBy('name')->get(),
        ]);
    }

    public function assign(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'user_id' => ['required', 'integer',
                Rule::exists('users', 'id')->where('role', User::ROLE_COLLECTOR)],
            'locality_id' => ['required', 'integer', 'exists:localities,id'],
        ]);

        DB::transaction(function () use ($data, $request): void {
            // A sub-division has exactly one collector, and a collector serves
            // exactly one sub-division: clear the previous holder of either.
            CollectorAssignment::query()
                ->where('user_id', $data['user_id'])
                ->orWhere('locality_id', $data['locality_id'])
                ->delete();

            CollectorAssignment::query()->create([
                'user_id' => $data['user_id'],
                'locality_id' => $data['locality_id'],
                'is_active' => true,
                'assigned_by' => $request->user()->id,
                'assigned_at' => now(),
            ]);
        });

        return redirect()
            ->route('admin.collectors.index')
            ->with('status', 'Collector signed to that sub-division.');
    }

    public function revoke(CollectorAssignment $assignment): RedirectResponse
    {
        $assignment->update(['is_active' => false]);

        return redirect()
            ->route('admin.collectors.index')
            ->with('status', 'Collector unassigned from that sub-division.');
    }
}
