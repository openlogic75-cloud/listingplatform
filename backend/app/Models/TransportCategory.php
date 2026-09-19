<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

/**
 * A kind of transport or errand work (M18.1): bike delivery, auto/rickshaw,
 * truck/tempo, errand runner, and so on. Admin-managed; drivers tick the
 * active ones. Retiring deactivates rather than deletes so historical links
 * keep their name.
 */
class TransportCategory extends Model
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

    public function drivers(): BelongsToMany
    {
        return $this->belongsToMany(
            User::class,
            'transport_category_user',
        );
    }
}
