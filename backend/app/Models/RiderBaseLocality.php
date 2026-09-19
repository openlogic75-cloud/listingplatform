<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RiderBaseLocality extends Model
{
    protected $fillable = [
        'rider_base_operation_id',
        'locality_id',
    ];

    public function riderBaseOperation(): BelongsTo
    {
        return $this->belongsTo(RiderBaseOperation::class);
    }

    public function locality(): BelongsTo
    {
        return $this->belongsTo(Locality::class);
    }
}
