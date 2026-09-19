<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Product;

class CatalogController extends Controller
{
    public function __invoke()
    {
        // Guest website catalog (M2.4). Search covers title/description only;
        // PII is never searchable.
        $data = request()->validate([
            'q' => ['nullable', 'string', 'max:120'],
            'category' => ['nullable', 'string', 'in:'.implode(',', Product::CATEGORIES)],
        ]);

        $query = Product::query()
            ->active()
            ->with('vendor')
            ->latest();

        if (isset($data['q']) && $data['q'] !== '') {
            $term = $data['q'];
            $query->where(function ($q) use ($term): void {
                $q->where('title', 'like', "%{$term}%")
                    ->orWhere('description', 'like', "%{$term}%");
            });
        }

        if (isset($data['category'])) {
            $query->where('category', $data['category']);
        }

        return view('pages.catalog', [
            'products' => $query->limit(48)->get(),
            'filters' => $data,
        ]);
    }
}
