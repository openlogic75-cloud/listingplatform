<?php

namespace App\Policies;

use App\Models\Booking;
use App\Models\User;

/**
 * Booking access: the vendor who received the booking (or an admin) may view
 * and transition it. Guests access their own booking only through the
 * code + phone lookup, never through this policy.
 */
class BookingPolicy
{
    public function view(User $user, Booking $booking): bool
    {
        if ($user->role === User::ROLE_ADMIN) {
            return true;
        }

        return $user->role === User::ROLE_VENDOR
            && $booking->vendor !== null
            && $booking->vendor->user_id === $user->id;
    }

    public function transition(User $user, Booking $booking): bool
    {
        return $this->view($user, $booking);
    }
}
