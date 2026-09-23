<?php

namespace App\Http\Requests;

use App\Models\Product;
use App\Models\Vendor;
use Illuminate\Validation\Rule;

/**
 * Partial update: every field optional, but category-dependent rules still
 * apply (rentals keep requiring the date window; non-rentals keep forbidding
 * stock and MOQ).
 */
class UpdateListingRequest extends ListingRequest
{
    protected function policyAbility(): string
    {
        return 'update';
    }

    public function rules(): array
    {
        $imageConsentRules = config('app.require_listing_image_consent')
            && count((array) $this->input('images', [])) > 0
            ? ['required', 'accepted']
            : ['nullable'];

        return [
            'title' => ['sometimes', 'string', 'max:120'],
            'category' => ['sometimes', 'string', 'in:'.implode(',', Vendor::CATEGORIES)],
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
            'images' => ['nullable', 'array', 'max:'.Product::MAX_IMAGES],
            'images.*' => ['string', 'max:255'],
            'image_public_consent' => $imageConsentRules,
            'district_id' => ['nullable', 'integer', 'exists:districts,id'],
            'locality_id' => ['nullable', 'integer', 'exists:localities,id'],
            'status' => ['sometimes', 'string', 'in:draft,active,inactive,archived'],
        ];
    }
}
