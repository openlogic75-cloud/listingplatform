<?php

namespace App\Policies;

use App\Models\Product;
use App\Models\User;

/**
 * Listing ownership. Vendors manage their own products; admins override.
 * Product auto-discovers this policy by convention (App\Models\Product).
 */
class ProductPolicy
{
    public function create(User $user): bool
    {
        return $user->role === User::ROLE_VENDOR || $user->role === User::ROLE_ADMIN;
    }

    public function update(User $user, Product $product): bool
    {
        if ($user->role === User::ROLE_ADMIN) {
            return true;
        }

        return $user->role === User::ROLE_VENDOR
            && $product->vendor !== null
            && $product->vendor->user_id === $user->id;
    }

    public function delete(User $user, Product $product): bool
    {
        return $this->update($user, $product);
    }
}
