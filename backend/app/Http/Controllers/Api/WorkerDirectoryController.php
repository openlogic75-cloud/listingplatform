<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\SkillCategory;
use App\Models\User;
use App\Models\WorkerProfile;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Skilled-worker directory for the app (M17.3). Public, no auth (buyers
 * browse as guests). Filter by canonical skill category; no contact PII is
 * returned — name, area, skills and the worker's own description only.
 */
class WorkerDirectoryController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $data = $request->validate([
            'category_id' => ['nullable', 'integer', 'exists:skill_categories,id'],
        ]);

        $workers = WorkerProfile::query()
            ->whereHas('user', fn ($query) => $query
                ->where('is_active', true)
                ->where('role', User::ROLE_SKILLED_WORKER))
            ->with([
                'user.district',
                'skillCategories' => fn ($query) => $query->where('is_active', true),
            ])
            ->when($data['category_id'] ?? null, fn ($query, $categoryId) => $query
                ->whereHas('skillCategories', fn ($skills) => $skills
                    ->where('skill_categories.id', $categoryId)))
            ->latest('id')
            ->limit(60)
            ->get();

        return response()->json([
            'data' => $workers->map(fn (WorkerProfile $worker) => [
                'id' => $worker->user_id,
                'name' => $worker->user?->name,
                'district' => $worker->user?->district?->name,
                'services' => $worker->services,
                'skill_categories' => $worker->skillCategories
                    ->map(fn (SkillCategory $category) => [
                        'id' => $category->id,
                        'name' => $category->name,
                    ])
                    ->all(),
            ]),
            'meta' => [
                'categories' => SkillCategory::query()
                    ->active()
                    ->orderBy('name')
                    ->get(['id', 'name']),
            ],
        ]);
    }
}
