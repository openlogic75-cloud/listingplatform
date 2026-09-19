<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\District;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Dedicated PG / rentals / homestays section (M23.1). Same listings as the
 * catalog but its own search surface: accommodation only, with area and price
 * filters, so guests can look at stays directly.
 */
class StayController extends Controller
{
    public function index(Request $request): View
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
        ]);

        $products = Product::query()
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
            ->limit(48)
            ->get();

        return view('pages.stays', [
            'products' => $products,
            'filters' => $data,
            'districts' => District::query()
                ->where('is_active', true)
                ->with(['localities' => fn ($query) => $query
                    ->where('is_active', true)
                    ->orderBy('name')])
                ->orderBy('name')
                ->get(),
        ]);
    }
}
