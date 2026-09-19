<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class WorkerProfile extends Model
{
    protected $fillable = [
        'user_id',
        'services',
        'service_areas',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Canonical skill categories the worker ticked (M17.2). Custom work
     * lives in the free-text `services` field.
     */
    public function skillCategories(): BelongsToMany
    {
        return $this->belongsToMany(
            SkillCategory::class,
            'skill_category_worker_profile',
        );
    }
}
