<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\VendorSalesReport;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Validation\ValidationException;

/**
 * Vendor sales report download (M21.1). Completed bookings only; the figures
 * are for the vendor's own records — settlement happens directly between the
 * parties and the platform holds no money.
 */
class VendorReportController extends Controller
{
    public function __construct(private VendorSalesReport $sales) {}

    public function sales(Request $request): Response
    {
        $user = $request->user();

        if ($user->role !== User::ROLE_VENDOR || $user->vendor === null) {
            throw ValidationException::withMessages([
                'role' => ['Only vendors have a sales report.'],
            ]);
        }

        $report = $this->sales->forVendor($user->vendor);

        $pdf = Pdf::loadView('reports.vendor-sales', [
            'vendor' => $user->vendor,
            'report' => $report,
            'generatedAt' => now(),
        ]);

        $filename = 'sales-'.now()->format('Y-m-d').'.pdf';

        return $pdf->download($filename);
    }
}
