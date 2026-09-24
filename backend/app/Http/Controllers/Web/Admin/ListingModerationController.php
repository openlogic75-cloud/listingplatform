<?php

namespace App\Http\Controllers\Web\Admin;

use App\Http\Controllers\Controller;
use App\Models\Product;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ListingModerationController extends Controller
{
    public function index(): View
    {
        return view('admin/listings/index', [
            'listings' => Product::query()
                ->where('status', Product::STATUS_PENDING)
                ->with('vendor')
                ->latest('id')
                ->paginate(30),
        ]);
    }

    public function approve(Request $request, Product $product): RedirectResponse
    {
        abort_unless($product->status === Product::STATUS_PENDING, 422);

        $product->update(['status' => Product::STATUS_ACTIVE, 'unpublished_at' => null]);

        return redirect()
            ->route('admin.listings.index')
            ->with('status', 'Listing approved and published.');
    }

    public function reject(Request $request, Product $product): RedirectResponse
    {
        abort_unless($product->status === Product::STATUS_PENDING, 422);

        $product->update(['status' => Product::STATUS_INACTIVE, 'unpublished_at' => now()]);

        return redirect()
            ->route('admin.listings.index')
            ->with('status', 'Listing rejected and kept unpublished.');
    }
}
