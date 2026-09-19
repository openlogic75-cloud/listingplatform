<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreListingRequest;
use App\Http\Requests\UpdateListingRequest;
use App\Http\Resources\ProductResource;
use App\Models\Product;
use App\Models\Vendor;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Vendor listing CRUD (M2.2). Ownership is enforced by ProductPolicy.
 * The vendor relationship is created lazily for admins testing the API.
 */
class ListingController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $vendor = $this->vendorFor($request);

        $products = $vendor
            ? $vendor->products()->latest()->get()
            : collect();

        return response()->json([
            'data' => ProductResource::collection($products),
        ]);
    }

    public function store(StoreListingRequest $request): JsonResponse
    {
        $this->authorize('create', Product::class);

        $vendor = $this->requireVendor($request);
        $validated = $request->safe()->except(['images']);
        $validated['images'] = $request->validatedImages();

        $product = $vendor->products()->create($validated + [
            'status' => $request->input('status', Product::STATUS_DRAFT),
        ]);

        return response()->json([
            'data' => new ProductResource($product->load('vendor')),
        ], 201);
    }

    public function update(UpdateListingRequest $request, Product $product): JsonResponse
    {
        $this->authorize('update', $product);

        $validated = $request->safe()->except(['images']);
        $validated['images'] = $request->validatedImages();

        $product->update($validated);

        return response()->json([
            'data' => new ProductResource($product->fresh('vendor')),
        ]);
    }

    public function destroy(Request $request, Product $product): JsonResponse
    {
        $this->authorize('delete', $product);

        $product->update(['status' => Product::STATUS_ARCHIVED]);

        return response()->json([
            'message' => 'Listing archived.',
        ]);
    }

    private function vendorFor(Request $request): ?Vendor
    {
        return Vendor::query()->where('user_id', $request->user()->id)->first();
    }

    private function requireVendor(Request $request): Vendor
    {
        $vendor = $this->vendorFor($request);

        if ($vendor === null) {
            if ($request->user()->role !== 'admin') {
                abort(403, 'Only vendors can create listings.');
            }

            $vendor = Vendor::query()->create([
                'user_id' => $request->user()->id,
                'display_name' => 'Platform administration',
                'category' => Vendor::CATEGORY_TRADITIONAL,
            ]);
        }

        return $vendor;
    }
}
