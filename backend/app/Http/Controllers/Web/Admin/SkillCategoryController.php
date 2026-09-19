<?php

namespace App\Http\Controllers\Web\Admin;

use App\Http\Controllers\Controller;
use App\Models\SkillCategory;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

/**
 * Skill categories (M17.2). The canonical list workers tick when describing
 * their work; admin-managed like districts/localities. Add takes a
 * comma-separated list; rows rename and activate/deactivate in place.
 */
class SkillCategoryController extends Controller
{
    public function index(): View
    {
        return view('admin.categories.index', [
            'heading' => 'Skill categories',
            'lede' => 'The canonical kinds of skilled work. Workers tick these when listing what they do, and buyers filter the skilled-worker directory by them. Retired categories are hidden, not deleted.',
            'routeBase' => 'admin.skills',
            'noun' => 'category',
            'placeholder' => 'Electrician, Plumber, Carpenter',
            'usageLabel' => 'Workers',
            'categories' => SkillCategory::query()
                ->withCount('workerProfiles')
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

        $existing = SkillCategory::query()
            ->pluck('name')
            ->map(fn (string $name) => mb_strtolower($name))
            ->all();

        $toCreate = $names->reject(
            fn (string $name) => in_array(mb_strtolower($name), $existing, true),
        );

        foreach ($toCreate as $name) {
            SkillCategory::query()->create([
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
            ->route('admin.skills.index')
            ->with('status', $summary.'.');
    }

    public function update(Request $request, SkillCategory $skillCategory): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:80'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $skillCategory->name = $data['name'];
        $skillCategory->is_active = $request->boolean('is_active');
        $skillCategory->save();

        return redirect()
            ->route('admin.skills.index')
            ->with('status', 'Category updated.');
    }
}
