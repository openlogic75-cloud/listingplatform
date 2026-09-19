<?php

namespace App\Http\Controllers\Web\Admin;

use App\Http\Controllers\Controller;
use App\Models\District;
use App\Models\Locality;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

/**
 * District and locality management (M4.1). Everything downstream (listings,
 * rider bases, matching) references these IDs, so renaming a locality is a
 * single row edit and adding one needs no code change.
 *
 * Localities are archived via is_active rather than deleted: historical rider
 * bases and products must keep resolving their names.
 */
class LocalityController extends Controller
{
    public function index(): View
    {
        $districts = District::query()
            ->with(['localities' => fn ($query) => $query->orderBy('name')])
            ->orderBy('name')
            ->get();

        return view('admin.localities.index', [
            'districts' => $districts,
        ]);
    }

    public function storeDistrict(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:120', 'unique:districts,name'],
        ]);

        District::query()->create([
            'name' => $data['name'],
            'is_active' => true,
        ]);

        return redirect()
            ->route('admin.localities.index')
            ->with('status', 'District added.');
    }

    public function updateDistrict(Request $request, District $district): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'is_active' => ['nullable', 'boolean'],
            'is_hub' => ['nullable', 'boolean'],
        ]);

        $district->name = $data['name'];
        $district->is_active = $request->boolean('is_active');
        // Hub districts are where collectors deliver farm produce (M28.1).
        $district->is_hub = $request->boolean('is_hub');
        $district->save();

        return redirect()
            ->route('admin.localities.index')
            ->with('status', 'District updated.');
    }

    public function storeLocality(Request $request, District $district): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:2000'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        // One submission may carry a comma-separated list (M10.1):
        // "Centre, East, West" creates three rows. Names are trimmed,
        // de-duplicated within the submission (case-insensitively), and
        // names that already exist in the district are skipped rather than
        // rejected, so re-pasting a list is safe.
        $names = collect(explode(',', $data['name']))
            ->map(fn (string $name) => trim($name))
            ->filter(fn (string $name) => $name !== '')
            ->unique(fn (string $name) => mb_strtolower($name))
            ->values();

        if ($names->isEmpty()) {
            throw ValidationException::withMessages([
                'name' => ['Enter at least one locality name.'],
            ]);
        }

        $tooLong = $names->first(fn (string $name) => mb_strlen($name) > 120);

        if ($tooLong !== null) {
            throw ValidationException::withMessages([
                'name' => ["Each locality name must be 120 characters or fewer (\"{$tooLong}\" is too long)."],
            ]);
        }

        $existing = Locality::query()
            ->where('district_id', $district->id)
            ->pluck('name')
            ->map(fn (string $name) => mb_strtolower($name))
            ->all();

        $toCreate = $names->reject(
            fn (string $name) => in_array(mb_strtolower($name), $existing, true),
        );

        // New localities start inactive unless the admin ticks Active —
        // then users only ever see areas with service (M9.1).
        $isActive = $request->boolean('is_active');

        foreach ($toCreate as $name) {
            Locality::query()->create([
                'district_id' => $district->id,
                'name' => $name,
                'is_active' => $isActive,
            ]);
        }

        $added = $toCreate->count();
        $skipped = $names->count() - $added;
        $summary = $added.' '.Str::plural('locality', $added).' added';

        if ($skipped > 0) {
            $summary .= ', '.$skipped.' already existed';
        }

        return redirect()
            ->route('admin.localities.index')
            ->with('status', $summary.($isActive
                ? ' — active service area'.($added === 1 ? '' : 's').'.'
                : '. Hidden until you tick Active.'));
    }

    public function updateLocality(Request $request, Locality $locality): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $locality->name = $data['name'];
        $locality->is_active = $request->boolean('is_active');
        $locality->save();

        return redirect()
            ->route('admin.localities.index')
            ->with('status', 'Locality updated.');
    }
}
