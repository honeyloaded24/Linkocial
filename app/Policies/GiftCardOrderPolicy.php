<?php

namespace App\Policies;

use App\Models\GiftCardOrder;
use App\Models\User;

class GiftCardOrderPolicy
{
    /**
     * Determine whether the user can view any gift card orders.
     */
    public function viewAny(User $user): bool
    {
        return $user !== null;
    }

    /**
     * Determine whether the user can view the specific gift card order.
     * Restricts access strictly to the owning customer and administrators.
     */
    public function view(User $user, GiftCardOrder $order): bool
    {
        return $user->id === $order->user_id || $user->isAdmin();
    }

    /**
     * Determine whether the user can update the gift card order.
     * Strictly restricted to administrators.
     */
    public function update(User $user, GiftCardOrder $order): bool
    {
        return $user->isAdmin();
    }

    /**
     * Determine whether the user can delete the gift card order.
     * Strictly restricted to administrators.
     */
    public function delete(User $user, GiftCardOrder $order): bool
    {
        return $user->isAdmin();
    }
}
