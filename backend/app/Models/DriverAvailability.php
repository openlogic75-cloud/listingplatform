<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Online/offline availability toggle state per driver (M4.3). Only online
 * drivers enter the job-matching pools.
 */
class DriverAvailability extends Model
{
    protected $table = 'driver_availability';

    protected $fillable = [
        'user_id',
        'is_online',
        'last_online_at',
    ];

    protected function casts(): array
    {
        return [
            'is_online' => 'boolean',
            'last_online_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
