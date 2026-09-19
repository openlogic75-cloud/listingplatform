<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Vendor extends Model
{
    protected $fillable = [
        'user_id',
        'display_name',
        'category',
        'description',
        'district_id',
        'locality_id',
        'address',
    ];

    public const CATEGORY_TRADITIONAL = 'traditional';
    public const CATEGORY_AGRO = 'agro';
    public const CATEGORY_RENTAL_HOMESTAY = 'rental_homestay';

    public const CATEGORIES = [
        self::CATEGORY_TRADITIONAL,
        self::CATEGORY_AGRO,
        self::CATEGORY_RENTAL_HOMESTAY,
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function district(): BelongsTo
    {
        return $this->belongsTo(District::class);
    }

    public function locality(): BelongsTo
    {
        return $this->belongsTo(Locality::class);
    }

    public function products(): HasMany
    {
        return $this->hasMany(Product::class);
    }

    public function bookings(): HasMany
    {
        return $this->hasMany(Booking::class);
    }

    /**
     * Live verified badge for this vendor, or null (M5.3).
     */
    public function getVerifiedBadgeAttribute(): ?Badge
    {
        return Badge::query()
            ->where('subject_type', self::class)
            ->where('subject_id', $this->id)
            ->whereNull('revoked_at')
            ->first();
    }
}
