<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class District extends Model
{
    protected $fillable = [
        'name',
        'is_active',
        'is_hub',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'is_hub' => 'boolean',
        ];
    }

    public function localities(): HasMany
    {
        return $this->hasMany(Locality::class);
    }
}
