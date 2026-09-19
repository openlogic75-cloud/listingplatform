<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Peer-to-peer referral/affiliate code (M6.3, extended M21.2). The owner —
 * a vendor or a driver — sets a commission they will pay an affiliate who
 * brings them business. The platform never moves money (Q1): commission is
 * recorded from what the two parties settled directly.
 */
class Referral extends Model
{
    public const COMMISSION_PERCENT = 'percent';

    public const COMMISSION_FIXED = 'fixed';

    public const COMMISSION_TYPES = [
        self::COMMISSION_PERCENT,
        self::COMMISSION_FIXED,
    ];

    protected $fillable = [
        'vendor_id',
        'owner_user_id',
        'code',
        'commission_type',
        'commission_value',
        'signups_count',
        'conversions_count',
    ];

    protected function casts(): array
    {
        return [
            'commission_value' => 'decimal:2',
            'signups_count' => 'integer',
            'conversions_count' => 'integer',
        ];
    }

    public function vendor(): BelongsTo
    {
        return $this->belongsTo(Vendor::class);
    }

    /**
     * The user who owns the code (a vendor's user or a driver).
     */
    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'owner_user_id');
    }

    public function events(): HasMany
    {
        return $this->hasMany(ReferralEvent::class);
    }

    /**
     * Commission for a given order value, from the owner's chosen rule.
     */
    public function commissionFor(?float $orderValue): float
    {
        if ($orderValue === null) {
            return 0.0;
        }

        return $this->commission_type === self::COMMISSION_PERCENT
            ? round($orderValue * ((float) $this->commission_value) / 100, 2)
            : round((float) $this->commission_value, 2);
    }

    /**
     * Human-readable commission, e.g. "5%" or "Rs. 50.00".
     */
    public function commissionLabel(): string
    {
        if ($this->commission_type === self::COMMISSION_PERCENT) {
            return rtrim(rtrim((string) $this->commission_value, '0'), '.').'%';
        }

        return config('app.currency_symbol', 'Rs. ').number_format((float) $this->commission_value, 2);
    }
}
