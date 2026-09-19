<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreListingRequest;
use App\Http\Requests\UpdateListingRequest;
use App\Models\Media;
use App\Models\Product;
use App\Support\UploadValidator;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
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

        $validated = $request->safe()->except(['images']);
        $validated['images'] = $this->storePhotos($request);

        $vendor->products()->create($validated + [
            'status' => $request->input('status', Product::STATUS_DRAFT),
        ]);

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
            'status' => ['required', 'string', 'in:draft,active,inactive,archived'],
        ]);

        $product->update(['status' => $data['status']]);

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
            'remove_photos' => ['nullable', 'array', 'max:8'],
            'remove_photos.*' => ['string', 'max:255'],
        ]);

        $removed = (array) $request->input('remove_photos', []);

        $kept = collect($product->images ?? [])
            ->reject(fn (string $path) => in_array($path, $removed, true))
            ->values()
            ->all();

        $validated = $request->safe()->except(['images']);
        $validated['images'] = [...$kept, ...$this->storePhotos($request)];

        $product->update($validated);

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
            'photos' => ['nullable', 'array', 'max:8'],
            'photos.*' => ['file', 'max:'.UploadValidator::MAX_KILOBYTES],
        ]);

        $paths = [];

        foreach (array_slice($request->file('photos') ?? [], 0, 8) as $file) {
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
