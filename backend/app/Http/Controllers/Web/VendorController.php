<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Vendor;
use App\Models\VerificationFeeSetting;

class VendorController extends Controller
{
    /**
     * Public vendor landing page (M2.5). Business identity only — no
     * personal contact details.
     */
    public function show(Vendor $vendor)
    {
        $products = $vendor->products()
            ->active()
            ->latest()
            ->get();

        return view('pages.vendor', [
            'vendor' => $vendor,
            'products' => $products,
            'verificationFee' => VerificationFeeSetting::current()->amount_inr,
        ]);
    }
}
