<?php

namespace App\Rules;

use App\Models\User;
use App\Support\BlindIndex;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

/**
 * Phone-number uniqueness against the blind index (M15.3/M12.3). Shared by
 * the member profile forms so the check never forks; the API's profile
 * update does not yet use it (its registration path does).
 */
class UniquePhone implements ValidationRule
{
    public function __construct(private readonly User $ignore) {}

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if ($value === null || $value === '') {
            return;
        }

        $taken = User::query()
            ->where('phone_index', BlindIndex::make((string) $value))
            ->whereKeyNot($this->ignore->getKey())
            ->exists();

        if ($taken) {
            $fail('This phone number is already registered.');
        }
    }
}
