<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreListingRequest;
use App\Http\Requests\UpdateListingRequest;
use App\Models\Consent;
use App\Models\Media;
use App\Models\Product;
use App\Support\UploadValidator;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

/**
 * Website listing management for vendors (M12.1). Reuses the API's
 * FormRequests so the field rules never fork, and the shared upload
 * validator (M2.3/M11.1) so photos are validated and converted identically.
 * Ownership is enforced by ProductPolicy.
 */
class VendorListingController extends Controller
{
    public function create(): View
    {
        $this->authorize('create', Product::class);

        return view('dashboard.listing-form', [
            'product' => null,
            'categories' => $this->categories(),
        ]);
    }

    public function store(StoreListingRequest $request): RedirectResponse
    {
        $this->authorize('create', Product::class);

        $vendor = $request->user()->vendor;

        if ($vendor === null) {
            abort(403);
        }

        if (config('app.require_listing_image_consent')
            && $request->hasFile('photos')
            && ! $request->boolean('image_public_consent')) {
            throw ValidationException::withMessages([
                'image_public_consent' => ['Confirm that listing images may be shown publicly.'],
            ]);
        }

        $validated = $request->safe()->except(['images', 'image_public_consent']);
        $validated['images'] = $this->storePhotos($request);

        $product = $vendor->products()->create(array_merge($validated, [
            'status' => config('app.require_listing_approval')
                ? Product::STATUS_PENDING
                : $request->input('status', Product::STATUS_DRAFT),
        ]));

        if ($validated['images'] !== []) {
            $this->recordPublicImageConsent($product);
        }

        return redirect()
            ->route('dashboard')
            ->with('status', 'Listing created.');
    }

    /**
     * One-tap status change from the listings table (M12.2): publish, pause
     * or archive. The full form still edits the details.
     */
    public function status(Request $request, Product $product): RedirectResponse
    {
        $this->authorize('update', $product);

        $data = $request->validate([
            'status' => ['required', 'string', 'in:draft,active,inactive,archived,pending'],
        ]);

        $product->update([
            'status' => config('app.require_listing_approval')
                && $data['status'] === Product::STATUS_ACTIVE
                ? Product::STATUS_PENDING
                : $data['status'],
        ]);

        return redirect()
            ->route('dashboard')
            ->with('status', 'Listing updated.');
    }

    public function edit(Product $product): View
    {
        $this->authorize('update', $product);

        return view('dashboard.listing-form', [
            'product' => $product,
            'categories' => $this->categories(),
        ]);
    }

    public function update(UpdateListingRequest $request, Product $product): RedirectResponse
    {
        $this->authorize('update', $product);

        $request->validate([
            'remove_photos' => ['nullable', 'array', 'max:'.Product::MAX_IMAGES],
            'remove_photos.*' => ['string', 'max:255'],
        ]);

        $removed = (array) $request->input('remove_photos', []);

        $kept = collect($product->images ?? [])
            ->reject(fn (string $path) => in_array($path, $removed, true))
            ->values()
            ->all();

        if (count($kept) + count($request->file('photos') ?? []) > Product::MAX_IMAGES) {
            throw ValidationException::withMessages([
                'photos' => ['A listing may have at most '.Product::MAX_IMAGES.' photos.'],
            ]);
        }

        if (config('app.require_listing_image_consent')
            && $request->hasFile('photos')
            && ! $request->boolean('image_public_consent')) {
            throw ValidationException::withMessages([
                'image_public_consent' => ['Confirm that listing images may be shown publicly.'],
            ]);
        }

        $validated = $request->safe()->except(['images', 'image_public_consent']);
        $newPhotos = $this->storePhotos($request);
        $validated['images'] = [...$kept, ...$newPhotos];

        if (config('app.require_listing_approval')) {
            $validated['status'] = Product::STATUS_PENDING;
        }

        $product->update($validated);

        if ($newPhotos !== []) {
            $this->recordPublicImageConsent($product);
        }

        return redirect()
            ->route('dashboard')
            ->with('status', 'Listing updated.');
    }

    /**
     * Validate and store the submitted photos through the shared validator,
     * recording a Media row for each so later edits can reference them.
     *
     * @return list<string>
     */
    private function storePhotos(Request $request): array
    {
        $request->validate([
            'photos' => ['nullable', 'array', 'max:'.Product::MAX_IMAGES],
            'photos.*' => ['file', 'max:'.UploadValidator::MAX_KILOBYTES],
        ]);

        $paths = [];

        foreach (array_slice($request->file('photos') ?? [], 0, Product::MAX_IMAGES) as $file) {
            $stored = UploadValidator::validateAndStore($file, 'products');

            Media::query()->create([
                'disk' => 'public',
                'path' => $stored['path'],
                'mime_type' => $stored['mime'],
                'size' => $stored['size'],
                'uploaded_by' => $request->user()->id,
            ]);

            $paths[] = $stored['path'];
        }

        return $paths;
    }

    private function recordPublicImageConsent(Product $product): void
    {
        Consent::query()->create([
            'subject_type' => Product::class,
            'subject_id' => $product->id,
            'consent_key' => Consent::KEY_LISTING_IMAGES_PUBLIC,
            'text_version' => '1.0',
            'purpose' => 'The vendor agreed that listing images are publicly viewable with the listing.',
            'granted_at' => now(),
        ]);
    }

    /**
     * @return array<string, string>
     */
    private function categories(): array
    {
        return [
            Product::CATEGORY_AGRO => 'Agro products',
            Product::CATEGORY_TRADITIONAL => 'Traditional products',
            Product::CATEGORY_RENTAL_HOMESTAY => 'Rental / Homestay',
        ];
    }
}
