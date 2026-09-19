<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Referral;
use App\Models\ReferralEvent;
use App\Models\User;
use App\Services\ReferralService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

/**
 * Website affiliate-code management (M21.2). Vendors and drivers create codes
 * and set the commission they will pay an affiliate — a percentage of the
 * order value or a fixed amount, their choice, per code. The platform never
 * moves money; the owner pays the affiliate directly.
 */
class ReferralController extends Controller
{
    public function __construct(private ReferralService $referrals) {}

    public function store(Request $request): RedirectResponse
    {
        $user = $request->user();

        // The code belongs to the vendor's shop, or to a driver directly.
        $owner = $user->vendor ?? ($user->role === User::ROLE_DRIVER ? $user : null);

        if ($owner === null) {
            throw ValidationException::withMessages([
                'role' => ['Only vendors and drivers can create affiliate codes.'],
            ]);
        }

        $data = $request->validate([
            'label' => ['nullable', 'string', 'max:40'],
            'commission_type' => ['required', 'string', 'in:'.implode(',', Referral::COMMISSION_TYPES)],
            'commission_value' => ['required', 'numeric', 'min:0', 'max:1000000'],
        ]);

        $this->referrals->generateCode(
            $owner,
            $data['label'] ?? null,
            $data['commission_type'],
            (float) $data['commission_value'],
        );

        return redirect()
            ->route('dashboard')
            ->with('status', 'Affiliate code created.');
    }

    /**
     * Owner approves a recorded commission (M21.2). They only pay people they
     * know and choose to; the platform moves no money.
     */
    public function approve(Request $request, ReferralEvent $event): RedirectResponse
    {
        $this->authorizeOwner($request, $event);

        $this->referrals->approveConversion($event, $request->user());

        return redirect()
            ->route('dashboard')
            ->with('status', 'Commission approved.');
    }

    public function reject(Request $request, ReferralEvent $event): RedirectResponse
    {
        $this->authorizeOwner($request, $event);

        $this->referrals->rejectConversion($event, $request->user());

        return redirect()
            ->route('dashboard')
            ->with('status', 'Commission declined.');
    }

    private function authorizeOwner(Request $request, ReferralEvent $event): void
    {
        $event->loadMissing('referral');

        if ($event->referral?->owner_user_id !== $request->user()->id) {
            abort(403);
        }
    }
}
