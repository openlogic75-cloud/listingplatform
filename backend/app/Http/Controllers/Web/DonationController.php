<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\DonationSetting;

class DonationController extends Controller
{
    /**
     * Donation page (M6.1 lite). Displays the admin-managed UPI ID and QR
     * image. The platform never stores payment data - this is display-only.
     * The admin settings screen lands in M6.2.
     */
    public function show()
    {
        return view('pages.donation', [
            'donation' => DonationSetting::query()->first(),
        ]);
    }
}
