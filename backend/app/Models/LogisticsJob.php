<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Pickup, delivery and farm-produce collection jobs. Delivery matching (M4.4)
 * uses the driver's base and online state; collection jobs (M28.3) go to the
 * collector signed to the pickup sub-division instead.
 */
class LogisticsJob extends Model
{
    public const TYPE_PICKUP = 'pickup';

    public const TYPE_DELIVERY = 'delivery';

    public const TYPE_COLLECT_PRODUCE = 'collect_produce';

    public const STATUS_REQUESTED = 'requested';

    public const STATUS_ASSIGNED = 'assigned';

    public const STATUS_ACCEPTED = 'accepted';

    public const STATUS_IN_PROGRESS = 'in_progress';

    public const STATUS_COMPLETED = 'completed';

    public const STATUS_CANCELLED = 'cancelled';

    protected $fillable = [
        'booking_id',
        'vendor_id',
        'type',
        'collector_id',
        'driver_id',
        'district_id',
        'locality_id',
        'drop_district_id',
        'fee_inr',
        'address',
        'status',
        'assigned_at',
        'completed_at',
    ];

    protected function casts(): array
    {
        return [
            'fee_inr' => 'decimal:2',
            'assigned_at' => 'datetime',
            'completed_at' => 'datetime',
        ];
    }

    public function booking(): BelongsTo
    {
        return $this->belongsTo(Booking::class);
    }

    public function vendor(): BelongsTo
    {
        return $this->belongsTo(Vendor::class);
    }

    public function collector(): BelongsTo
    {
        return $this->belongsTo(User::class, 'collector_id');
    }

    public function driver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'driver_id');
    }

    public function district(): BelongsTo
    {
        return $this->belongsTo(District::class);
    }

    public function locality(): BelongsTo
    {
        return $this->belongsTo(Locality::class);
    }

    /**
     * Destination hub for a collection job (M28.3).
     */
    public function destinationDistrict(): BelongsTo
    {
        return $this->belongsTo(District::class, 'drop_district_id');
    }
}
