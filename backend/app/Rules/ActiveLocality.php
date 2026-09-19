<?php

namespace App\Rules;

use App\Models\Locality;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

/**
 * Locality reference rule (M9.1/M10.2): the value must exist and, when a
 * district is given, belong to that district. When `$enforceServiceArea` is
 * on (the default) it must also sit in an active service area — locality
 * active AND its district active.
 *
 * The admin's Active tick gates the delivery/errand flows: driver bases,
 * errands and logistics jobs. Listings pass `enforceServiceArea: false`
 * (owner decision 2026-09-18) — the enable/disable concept is only for
 * driver and errands, so a listing may reference any existing locality.
 * This is the only place locality validation lives.
 */
class ActiveLocality implements ValidationRule
{
    public function __construct(
        private readonly mixed $districtId = null,
        private readonly string $inactiveMessage = 'The selected locality is not an active service area.',
        private readonly bool $enforceServiceArea = true,
    ) {}

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        /** @var Locality|null $locality */
        $locality = Locality::query()->with('district')->find($value);

        if ($locality === null) {
            $fail('The selected locality does not exist.');

            return;
        }

        $districtId = $this->districtId instanceof Closure
            ? ($this->districtId)()
            : $this->districtId;

        if ($districtId !== null && (int) $districtId !== $locality->district_id) {
            $fail('The selected locality must belong to the selected district.');

            return;
        }

        if ($this->enforceServiceArea
            && (! $locality->is_active || ! $locality->district?->is_active)) {
            $fail($this->inactiveMessage);
        }
    }
}
