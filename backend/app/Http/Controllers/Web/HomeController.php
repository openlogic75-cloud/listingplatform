<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Product;

class HomeController extends Controller
{
    public function __invoke()
    {
        return view('pages.home', [
            // A few accommodation listings to promote the dedicated section
            // (M23.1) on the home page.
            'stays' => Product::query()
                ->active()
                ->where('category', Product::CATEGORY_RENTAL_HOMESTAY)
                ->latest()
                ->limit(4)
                ->get(),
        ]);
    }
}
