<?php

namespace App\Policies;

use App\Models\Product;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class ProductPolicy
{
    /**
     * Admins can do everything. Returning null falls through to the checks below.
     */
    public function before(User $user): ?bool
    {
        return $user->is_admin ? true : null;
    }

    /**
     * Any signed-in user can browse the catalog.
     */
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, Product $product): bool
    {
        return true;
    }

    public function create(User $user): bool
    {
        return true;
    }

    /**
     * You can only change products you created.
     */
    public function update(User $user, Product $product): bool
    {
        return $user->id === $product->user_id;
    }

    public function delete(User $user, Product $product): bool
    {
        return $user->id === $product->user_id;
    }

    public function restore(User $user, Product $product): bool
    {
        return $user->id === $product->user_id;
    }

    public function forceDelete(User $user, Product $product): bool
    {
        return $user->id === $product->user_id;
    }
}
