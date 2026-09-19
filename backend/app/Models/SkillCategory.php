<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

/**
 * A canonical kind of skilled work (M17.2). Managed by admins; workers tick
 * the active ones. Retiring a category deactivates it rather than deleting,
 * so historical worker links keep resolving their name.
 */
class SkillCategory extends Model
{
    protected $fillable = [
        'name',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function workerProfiles(): BelongsToMany
    {
        return $this->belongsToMany(
            WorkerProfile::class,
            'skill_category_worker_profile',
        );
    }
}
