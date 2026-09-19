<?php

namespace App\Http\Controllers\Web\Admin;

use App\Http\Controllers\Controller;
use App\Models\Verification;
use App\Services\VerificationReviewService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Verification review queue for the admin dashboard (M9.5). Submitted
 * site-visit reports with volunteer name, notes, checklist and evidence
 * photos. Approvals and rejections run through VerificationReviewService —
 * the same rules the API uses — so the two surfaces never fork.
 */
class VerificationController extends Controller
{
    public function __construct(private VerificationReviewService $reviews) {}

    public function index(): View
    {
        $verifications = Verification::query()
            ->where('status', Verification::STATUS_SUBMITTED)
            ->with(['volunteer.user', 'subject'])
            ->latest()
            ->limit(50)
            ->get();

        return view('admin.verifications.index', [
            'verifications' => $verifications,
        ]);
    }

    public function approve(Request $request, Verification $verification): RedirectResponse
    {
        $badge = $this->reviews->approve($verification, $request->user());

        return redirect()
            ->route('admin.verifications.index')
            ->with('status', "Verification approved — badge issued, verified by {$badge->volunteer_name}.");
    }

    public function reject(Request $request, Verification $verification): RedirectResponse
    {
        $this->reviews->reject($verification, $request->user());

        return redirect()
            ->route('admin.verifications.index')
            ->with('status', 'Verification rejected and sent back to the volunteer.');
    }
}
