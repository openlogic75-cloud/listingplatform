<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\District;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Farm produce for resellers (M28.2). A dedicated section for bulk farm
 * produce, so resellers can find it directly and farmers can reach buyers who
 * will resell. Collectors move it from the sub-division to a hub district.
 */
class ResellerProduceController extends Controller
{
    public function index(Request $request): View
    {
        $data = $request->validate([
            'q' => ['nullable', 'string', 'max:120'],
            'district_id' => ['nullable', 'integer'],
            'locality_id' => ['nullable', 'integer'],
            'min_price' => ['nullable', 'numeric', 'min:0'],
            'max_price' => ['nullable', 'numeric', 'min:0', 'gte:min_price'],
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
            ->limit(48)
            ->get();

        return view('pages.reseller-produce', [
            'products' => $products,
            'filters' => $data,
            'hubs' => District::query()->where('is_hub', true)->orderBy('name')->get(),
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
