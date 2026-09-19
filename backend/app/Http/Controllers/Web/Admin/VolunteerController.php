<?php

namespace App\Http\Controllers\Web\Admin;

use App\Http\Controllers\Controller;
use App\Models\VerificationVolunteer;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Volunteer approval queue (M17.1). Registration creates volunteers as
 * pending; only an approved volunteer can sign in (web or app) and take
 * site visits. Mirrors the verification review queue (M9.5).
 */
class VolunteerController extends Controller
{
    public function index(): View
    {
        return view('admin.volunteers.index', [
            'pending' => VerificationVolunteer::query()
                ->where('verification_status', VerificationVolunteer::STATUS_PENDING)
                ->with('user')
                ->oldest()
                ->get(),
            'reviewed' => VerificationVolunteer::query()
                ->whereIn('verification_status', [
                    VerificationVolunteer::STATUS_APPROVED,
                    VerificationVolunteer::STATUS_REJECTED,
                ])
                ->with(['user', 'reviewedBy'])
                ->latest('reviewed_at')
                ->limit(25)
                ->get(),
        ]);
    }

    public function approve(Request $request, VerificationVolunteer $volunteer): RedirectResponse
    {
        $volunteer->update([
            'verification_status' => VerificationVolunteer::STATUS_APPROVED,
            'reviewed_by' => $request->user()->id,
            'reviewed_at' => now(),
        ]);

        return redirect()
            ->route('admin.volunteers.index')
            ->with('status', 'Volunteer approved — they can sign in now.');
    }

    public function reject(Request $request, VerificationVolunteer $volunteer): RedirectResponse
    {
        $volunteer->update([
            'verification_status' => VerificationVolunteer::STATUS_REJECTED,
            'reviewed_by' => $request->user()->id,
            'reviewed_at' => now(),
        ]);

        return redirect()
            ->route('admin.volunteers.index')
            ->with('status', 'Volunteer application rejected.');
    }
}
