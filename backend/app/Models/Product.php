<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

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
    ];

    public const CATEGORY_TRADITIONAL = 'traditional';

    public const CATEGORY_AGRO = 'agro';

    public const CATEGORY_RENTAL_HOMESTAY = 'rental_homestay';

    public const CATEGORIES = [
        self::CATEGORY_TRADITIONAL,
        self::CATEGORY_AGRO,
        self::CATEGORY_RENTAL_HOMESTAY,
    ];

    public const STATUS_DRAFT = 'draft';

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
        ];
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

    public function district(): BelongsTo
    {
        return $this->belongsTo(District::class);
    }

    public function locality(): BelongsTo
    {
        return $this->belongsTo(Locality::class);
    }
}
