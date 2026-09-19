<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Single admin-managed row: the verification fee in INR (M5.4). Display-only —
 * the platform never stores or processes payment credentials; the volunteer
 * collects the fee directly at the visit (AGENTS.md §7 money boundary).
 */
class VerificationFeeSetting extends Model
{
    protected $fillable = [
        'amount_inr',
    ];

    protected function casts(): array
    {
        return [
            'amount_inr' => 'decimal:2',
        ];
    }

    /**
     * Current fee row, creating the empty singleton on first read so the
     * admin form always has a row to edit.
     */
    public static function current(): self
    {
        return static::query()->firstOrCreate([], ['amount_inr' => null]);
    }
}