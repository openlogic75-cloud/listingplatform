<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\SkillCategory;
use App\Models\User;
use App\Models\WorkerProfile;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Public skilled-worker directory (M17.3), filterable by canonical skill
 * category. Guest-friendly. Contact details are deliberately NOT exposed
 * here — phone/email stay private; a request/contact flow is a separate
 * decision (see the tracker).
 */
class WorkerController extends Controller
{
    public function index(Request $request): View
    {
        $data = $request->validate([
            'category_id' => ['nullable', 'integer', 'exists:skill_categories,id'],
        ]);

        $selected = $data['category_id'] ?? null;

        $workers = WorkerProfile::query()
            ->whereHas('user', fn ($query) => $query
                ->where('is_active', true)
                ->where('role', User::ROLE_SKILLED_WORKER))
            ->with([
                'user.district',
                'skillCategories' => fn ($query) => $query->where('is_active', true),
            ])
            ->when($selected, fn ($query) => $query
                ->whereHas('skillCategories', fn ($skills) => $skills
                    ->where('skill_categories.id', $selected)))
            ->latest('id')
            ->limit(60)
            ->get();

        return view('pages.workers', [
            'workers' => $workers,
            'categories' => SkillCategory::query()->active()->orderBy('name')->get(),
            'selected' => $selected,
        ]);
    }
}
