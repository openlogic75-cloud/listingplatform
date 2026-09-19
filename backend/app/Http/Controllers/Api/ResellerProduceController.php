<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\ProductResource;
use App\Models\District;
use App\Models\Product;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Farm produce for resellers (M28.2), for the app. Accommodation-style
 * dedicated feed; hub districts are returned so the app can show where
 * collectors deliver.
 */
class ResellerProduceController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $data = $request->validate([
            'q' => ['nullable', 'string', 'max:120'],
            'district_id' => ['nullable', 'integer'],
            'locality_id' => ['nullable', 'integer'],
            'min_price' => ['nullable', 'numeric', 'min:0'],
            'max_price' => ['nullable', 'numeric', 'min:0', 'gte:min_price'],
            'page' => ['nullable', 'integer', 'min:1'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:50'],
        ]);

        $products = Product::query()
            ->active()
            ->where('category', Product::CATEGORY_FARM_RESELLER)
            ->with('vendor')
            ->when(
                isset($data['q']) && $data['q'] !== '',
                fn ($query) => $query->where(function ($term) use ($data): void {
                    $term->where('title', 'like', '%'.$data['q'].'%')
                        ->orWhere('description', 'like', '%'.$data['q'].'%');
                }),
            )
            ->when(isset($data['district_id']), fn ($query) => $query->where('district_id', $data['district_id']))
            ->when(isset($data['locality_id']), fn ($query) => $query->where('locality_id', $data['locality_id']))
            ->when(isset($data['min_price']), fn ($query) => $query->where('price', '>=', $data['min_price']))
            ->when(isset($data['max_price']), fn ($query) => $query->where('price', '<=', $data['max_price']))
            ->latest()
            ->paginate($data['per_page'] ?? 24);

        return response()->json([
            'data' => ProductResource::collection($products->items()),
            'meta' => [
                'current_page' => $products->currentPage(),
                'last_page' => $products->lastPage(),
                'total' => $products->total(),
                'hubs' => District::query()->where('is_hub', true)->orderBy('name')
                    ->get(['id', 'name']),
            ],
        ]);
    }
}
