<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Errands: ride-style pickup/drop tasks. Guests may request an errand with
 * only a contact name and phone (encrypted, purpose-limited, consented).
 */
class Errand extends Model
{
    public const STATUS_REQUESTED = 'requested';

    public const STATUS_ASSIGNED = 'assigned';

    public const STATUS_ACCEPTED = 'accepted';

    public const STATUS_IN_PROGRESS = 'in_progress';

    public const STATUS_COMPLETED = 'completed';

    public const STATUS_CANCELLED = 'cancelled';

    protected $fillable = [
        'code',
        'customer_id',
        'contact_name',
        'contact_phone',
        'contact_phone_index',
        'description',
        'pickup_district_id',
        'pickup_locality_id',
        'pickup_address',
        'drop_district_id',
        'drop_locality_id',
        'drop_address',
        'driver_id',
        'status',
        'referral_code',
    ];

    protected function casts(): array
    {
        return [
            'contact_name' => 'encrypted',
            'contact_phone' => 'encrypted',
        ];
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'customer_id');
    }

    public function driver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'driver_id');
    }

    public function pickupDistrict(): BelongsTo
    {
        return $this->belongsTo(District::class, 'pickup_district_id');
    }

    public function pickupLocality(): BelongsTo
    {
        return $this->belongsTo(Locality::class, 'pickup_locality_id');
    }

    public function dropDistrict(): BelongsTo
    {
        return $this->belongsTo(District::class, 'drop_district_id');
    }

    public function dropLocality(): BelongsTo
    {
        return $this->belongsTo(Locality::class, 'drop_locality_id');
    }
}
