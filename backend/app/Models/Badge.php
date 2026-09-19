<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

/**
 * Verified badge. Stores the volunteer's name (and photo, when set) as an
 * immutable snapshot at issue time so the attribution survives volunteer data
 * deletion (DPDP M7.3), and links to the signed verification story (M25.1).
 */
class Badge extends Model
{
    protected $fillable = [
        'subject_type',
        'subject_id',
        'volunteer_name',
        'volunteer_photo',
        'fee_inr',
        'post_id',
        'issued_at',
        'revoked_at',
    ];

    protected function casts(): array
    {
        return [
            'fee_inr' => 'decimal:2',
            'issued_at' => 'datetime',
            'revoked_at' => 'datetime',
        ];
    }

    public function subject(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * The volunteer-signed story that vouches for the verification.
     */
    public function post(): BelongsTo
    {
        return $this->belongsTo(Post::class);
    }
}
