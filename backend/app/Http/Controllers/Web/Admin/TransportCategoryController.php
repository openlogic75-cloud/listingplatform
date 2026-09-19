<?php

namespace App\Http\Controllers\Web\Admin;

use App\Http\Controllers\Controller;
use App\Models\TransportCategory;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

/**
 * Transport & errand categories (M18.1): the kinds of driving/errand work
 * drivers tick. Admin-managed like skill categories and districts; the view
 * is shared with the skills screen.
 */
class TransportCategoryController extends Controller
{
    public function index(): View
    {
        return view('admin.categories.index', [
            'heading' => 'Transport & errand categories',
            'lede' => 'The kinds of transport and errand work drivers can offer. Drivers tick these, and users filter the transport directory by them. Retired categories are hidden, not deleted.',
            'routeBase' => 'admin.transport',
            'noun' => 'category',
            'placeholder' => 'Bike delivery, Auto / rickshaw, Errand runner',
            'usageLabel' => 'Drivers',
            'categories' => TransportCategory::query()
                ->withCount('drivers')
                ->orderBy('name')
                ->get(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:2000'],
        ]);

        $names = collect(explode(',', $data['name']))
            ->map(fn (string $name) => trim($name))
            ->filter(fn (string $name) => $name !== '')
            ->unique(fn (string $name) => mb_strtolower($name))
            ->values();

        if ($names->isEmpty()) {
            throw ValidationException::withMessages([
                'name' => ['Enter at least one category.'],
            ]);
        }

        $tooLong = $names->first(fn (string $name) => mb_strlen($name) > 80);

        if ($tooLong !== null) {
            throw ValidationException::withMessages([
                'name' => ["Each category must be 80 characters or fewer (\"{$tooLong}\" is too long)."],
            ]);
        }

        $existing = TransportCategory::query()
            ->pluck('name')
            ->map(fn (string $name) => mb_strtolower($name))
            ->all();

        $toCreate = $names->reject(
            fn (string $name) => in_array(mb_strtolower($name), $existing, true),
        );

        foreach ($toCreate as $name) {
            TransportCategory::query()->create([
                'name' => $name,
                'is_active' => true,
            ]);
        }

        $added = $toCreate->count();
        $skipped = $names->count() - $added;
        $summary = $added.' '.Str::plural('category', $added).' added';

        if ($skipped > 0) {
            $summary .= ', '.$skipped.' already existed';
        }

        return redirect()
            ->route('admin.transport.index')
            ->with('status', $summary.'.');
    }

    public function update(Request $request, TransportCategory $transportCategory): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:80'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $transportCategory->name = $data['name'];
        $transportCategory->is_active = $request->boolean('is_active');
        $transportCategory->save();

        return redirect()
            ->route('admin.transport.index')
            ->with('status', 'Category updated.');
    }
}
