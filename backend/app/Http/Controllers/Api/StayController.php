<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\ProductResource;
use App\Models\Product;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * PG / rentals / homestays feed for the app (M23.1). Same filters as the
 * website section; accommodation only.
 */
class StayController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $data = $request->validate([
            'q' => ['nullable', 'string', 'max:120'],
            'district_id' => ['nullable', 'integer'],
            'locality_id' => ['nullable', 'integer'],
            'min_price' => ['nullable', 'numeric', 'min:0'],
            'max_price' => [
                'nullable',
                'numeric',
                'min:0',
                function (string $attribute, mixed $value, \Closure $fail) use ($request): void {
                    $min = $request->input('min_price');
                    if ($value !== null && $min !== null && $min !== '' && (float) $value < (float) $min) {
                        $fail('The max price must be at least the min price.');
                    }
                },
            ],
            'page' => ['nullable', 'integer', 'min:1'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:50'],
        ]);

        $stays = Product::query()
            ->active()
            ->where('category', Product::CATEGORY_RENTAL_HOMESTAY)
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
            'data' => ProductResource::collection($stays->items()),
            'meta' => [
                'current_page' => $stays->currentPage(),
                'last_page' => $stays->lastPage(),
                'total' => $stays->total(),
            ],
        ]);
    }
}
