<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

/**
 * A rider's base of operation: one district plus up to five localities.
 * The max-five rule is enforced in validation (M4.2), not in the schema.
 */
class RiderBaseOperation extends Model
{
    protected $fillable = [
        'user_id',
        'district_id',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function district(): BelongsTo
    {
        return $this->belongsTo(District::class);
    }

    public function localities(): BelongsToMany
    {
        return $this->belongsToMany(
            Locality::class,
            'rider_base_localities',
        );
    }
}
