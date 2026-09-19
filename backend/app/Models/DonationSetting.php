<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Single admin-managed row: UPI ID + UPI QR image for the donation page.
 * The platform stores no payment data - this is display-only (M6).
 */
class DonationSetting extends Model
{
    protected $fillable = [
        'upi_id',
        'qr_path',
    ];
}
