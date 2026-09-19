<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Locality extends Model
{
    protected $fillable = [
        'district_id',
        'name',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }

    public function district(): BelongsTo
    {
        return $this->belongsTo(District::class);
    }

    /**
     * Rider base operations covering this locality (many-to-many via
     * rider_base_localities, M4.2). Used by the driver-availability view to
     * count online drivers whose base covers each locality (M4.6).
     *
     * @return BelongsToMany<RiderBaseOperation>
     */
    public function riderBases(): BelongsToMany
    {
        return $this->belongsToMany(
            RiderBaseOperation::class,
            'rider_base_localities',
        );
    }

    /**
     * Online drivers whose base of operation covers this locality (M4.6, Q8).
     * scoped()/existence-constrained at query time; used by withCount().
     */
    public function driversAvailable(): BelongsToMany
    {
        return $this->riderBases()
            ->whereHas('user', function ($user) {
                $user->where('role', User::ROLE_DRIVER)
                    ->whereHas('driverAvailability', fn ($q) => $q->where('is_online', true));
            });
    }
}
