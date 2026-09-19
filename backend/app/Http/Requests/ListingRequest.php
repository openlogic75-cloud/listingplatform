<?php

namespace App\Http\Requests;

use App\Models\Media;
use App\Models\Vendor;
use App\Rules\ActiveLocality;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Shared field rules for creating and updating a listing (M2.2). Rentals and
 * homestays sell time windows (availability dates), not stock or MOQ.
 * Location fields only accept active service areas (M9.1).
 */
abstract class ListingRequest extends FormRequest
{
    public function authorize(): bool
    {
        // Policy checks (create/update ownership) run in the controller with
        // the bound model instance, via the AuthorizesRequests trait.
        return $this->user() !== null;
    }

    abstract protected function policyAbility(): string;

    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:120'],
            'category' => ['required', 'string', 'in:'.implode(',', Vendor::CATEGORIES)],
            'description' => ['nullable', 'string', 'max:5000'],
            'price' => [
                'nullable',
                'numeric',
                'min:0',
                'max:99999999.99',
                Rule::requiredIf($this->input('category') === Vendor::CATEGORY_RENTAL_HOMESTAY),
            ],
            'unit' => ['nullable', 'string', 'max:20'],
            'moq' => [
                'nullable',
                'integer',
                'min:1',
                'max:100000',
                Rule::prohibitedIf($this->input('category') === Vendor::CATEGORY_RENTAL_HOMESTAY),
            ],
            'stock' => [
                'nullable',
                'integer',
                'min:0',
                Rule::prohibitedIf($this->input('category') === Vendor::CATEGORY_RENTAL_HOMESTAY),
            ],
            'batch_code' => ['nullable', 'string', 'max:60'],
            'available_from' => [
                'nullable',
                'date',
                Rule::requiredIf($this->input('category') === Vendor::CATEGORY_RENTAL_HOMESTAY),
            ],
            'available_to' => [
                'nullable',
                'date',
                'after_or_equal:available_from',
                Rule::requiredIf($this->input('category') === Vendor::CATEGORY_RENTAL_HOMESTAY),
            ],
            'images' => ['nullable', 'array', 'max:8'],
            'images.*' => ['string', 'max:255'],
            'district_id' => [
                'nullable',
                'integer',
                Rule::exists('districts', 'id'),
            ],
            'locality_id' => [
                'nullable',
                'integer',
                // Existence + district pairing only: the service-area
                // enable/disable is for driver bases and errands, not
                // listings (M10.2).
                new ActiveLocality(
                    fn () => $this->input('district_id'),
                    enforceServiceArea: false,
                ),
            ],
            'status' => ['sometimes', 'string', 'in:draft,active,inactive,archived'],
        ];
    }

    /**
     * Image paths must reference media this user uploaded (M2.3) — prevents
     * referencing other users' media or arbitrary storage paths.
     */
    public function validatedImages(): array
    {
        $paths = array_values((array) $this->input('images', []));

        if ($paths === []) {
            return [];
        }

        return Media::query()
            ->where('uploaded_by', $this->user()->id)
            ->whereIn('path', $paths)
            ->pluck('path')
            ->values()
            ->all();
    }
}
