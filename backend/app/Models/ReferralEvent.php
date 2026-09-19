<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ReferralEvent extends Model
{
    public const TYPE_SIGNUP = 'signup';

    public const TYPE_CONVERSION = 'conversion';

    /**
     * A conversion is recorded automatically but only counts once the owner
     * approves it (M21.2) — the platform never pays anyone.
     */
    public const STATUS_PENDING = 'pending';

    public const STATUS_APPROVED = 'approved';

    public const STATUS_REJECTED = 'rejected';

    protected $fillable = [
        'referral_id',
        'type',
        'status',
        'attributed_user_id',
        'order_value',
        'amount_inr',
        'approved_by',
        'approved_at',
        'metadata',
    ];

    protected function casts(): array
    {
        return [
            'order_value' => 'decimal:2',
            'amount_inr' => 'decimal:2',
            'approved_at' => 'datetime',
            'metadata' => 'array',
        ];
    }

    public function referral(): BelongsTo
    {
        return $this->belongsTo(Referral::class);
    }

    public function attributedUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'attributed_user_id');
    }

    public function approvedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function isApproved(): bool
    {
        return $this->status === self::STATUS_APPROVED;
    }
}
