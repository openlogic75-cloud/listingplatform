<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\Vendor;
use Illuminate\Http\JsonResponse;

/**
 * Public vendor profile (M2.5): shop identity + its active listings.
 * Personal contact details stay private — the platform mediates contact.
 */
class VendorController extends Controller
{
    public function show(Vendor $vendor): JsonResponse
    {
        $products = $vendor->products()
            ->active()
            ->with('vendor')
            ->latest()
            ->get();

        return response()->json([
            'data' => [
                'id' => $vendor->id,
                'display_name' => $vendor->display_name,
                'category' => $vendor->category,
                'description' => $vendor->description,
                'district_id' => $vendor->district_id,
                'is_verified' => $vendor->verified_badge !== null,
                'listings' => \App\Http\Resources\ProductResource::collection($products)->resolve(),
            ],
        ]);
    }
}
