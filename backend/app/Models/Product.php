<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Product extends Model
{
    protected $fillable = [
        'vendor_id',
        'category',
        'title',
        'description',
        'price',
        'unit',
        'moq',
        'stock',
        'batch_code',
        'available_from',
        'available_to',
        'images',
        'district_id',
        'locality_id',
        'status',
        'unpublished_at',
    ];

    public const CATEGORY_TRADITIONAL = 'traditional';

    public const CATEGORY_AGRO = 'agro';

    public const CATEGORY_RENTAL_HOMESTAY = 'rental_homestay';

    /** Farm produce listed in bulk for resellers (M28.2). */
    public const CATEGORY_FARM_RESELLER = 'farm_reseller';

    /** Most photos one listing may carry (M30.1). Single source of truth. */
    public const MAX_IMAGES = 4;

    public const CATEGORIES = [
        self::CATEGORY_TRADITIONAL,
        self::CATEGORY_AGRO,
        self::CATEGORY_RENTAL_HOMESTAY,
        self::CATEGORY_FARM_RESELLER,
    ];

    public const STATUS_DRAFT = 'draft';

    /** New vendor listings await admin review before public publication. */
    public const STATUS_PENDING = 'pending';

    public const STATUS_ACTIVE = 'active';

    public const STATUS_INACTIVE = 'inactive';

    public const STATUS_ARCHIVED = 'archived';

    protected function casts(): array
    {
        return [
            'price' => 'decimal:2',
            'moq' => 'integer',
            'stock' => 'integer',
            'available_from' => 'date',
            'available_to' => 'date',
            'images' => 'array',
            'unpublished_at' => 'datetime',
        ];
    }

    /**
     * A listing may be deleted once it has been out of the public eye for
     * long enough: drafts (never published) anytime; inactive/archived only
     * after 7 full days unpublished. Active/pending listings are never
     * eligible — unpublish first.
     */
    public function isDeletionEligible(): bool
    {
        if ($this->status === self::STATUS_DRAFT) {
            return true;
        }

        if (! in_array($this->status, [self::STATUS_INACTIVE, self::STATUS_ARCHIVED], true)) {
            return false;
        }

        $since = $this->unpublished_at ?? $this->updated_at;

        return $since !== null && $since->lte(now()->subDays(7));
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('status', self::STATUS_ACTIVE);
    }

    public function getVerifiedBadgeAttribute(): ?Badge
    {
        return Badge::query()
            ->where('subject_type', self::class)
            ->where('subject_id', $this->id)
            ->whereNull('revoked_at')
            ->first();
    }

    public function vendor(): BelongsTo
    {
        return $this->belongsTo(Vendor::class);
    }

    public function bookingItems(): HasMany
    {
        return $this->hasMany(BookingItem::class);
    }

    public function district(): BelongsTo
    {
        return $this->belongsTo(District::class);
    }

    public function locality(): BelongsTo
    {
        return $this->belongsTo(Locality::class);
    }
}
