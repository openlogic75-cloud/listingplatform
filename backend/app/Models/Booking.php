<?php

namespace App\Models;

use App\Support\BlindIndex;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Guest bookings: a booking needs no account, only a contact name and phone
 * (purpose-limited, encrypted) plus a booking code for lookup (M3).
 */
class Booking extends Model
{
    public const STATUS_PENDING = 'pending';

    public const STATUS_CONFIRMED = 'confirmed';

    public const STATUS_PICKED_UP = 'picked_up';

    public const STATUS_IN_TRANSIT = 'in_transit';

    public const STATUS_DELIVERED = 'delivered';

    public const STATUS_COMPLETED = 'completed';

    public const STATUS_CANCELLED = 'cancelled';

    public const STATUSES = [
        self::STATUS_PENDING,
        self::STATUS_CONFIRMED,
        self::STATUS_PICKED_UP,
        self::STATUS_IN_TRANSIT,
        self::STATUS_DELIVERED,
        self::STATUS_COMPLETED,
        self::STATUS_CANCELLED,
    ];

    protected $fillable = [
        'code',
        'vendor_id',
        'status',
        'completed_at',
        'referral_code',
        'contact_name',
        'contact_phone',
        'contact_phone_index',
        'is_reseller',
        'notes',
        'settled_offline',
    ];

    protected function casts(): array
    {
        return [
            'contact_name' => 'encrypted',
            'contact_phone' => 'encrypted',
            'is_reseller' => 'boolean',
            'settled_offline' => 'boolean',
            'completed_at' => 'datetime',
        ];
    }

    /**
     * Guest lookup: booking code + matching phone blind index. The code alone
     * is never sufficient (it is printed on receipts).
     */
    public function scopeForGuest(Builder $query, string $code, string $phone): Builder
    {
        return $query->where('code', $code)
            ->where('contact_phone_index', BlindIndex::make($phone));
    }

    public function vendor(): BelongsTo
    {
        return $this->belongsTo(Vendor::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(BookingItem::class);
    }
}
