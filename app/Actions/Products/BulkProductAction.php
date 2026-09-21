<?php

namespace App\Actions\Products;

use App\Enums\ProductStatus;
use App\Models\Product;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;

class BulkProductAction
{
    /**
     * Soft delete the selected products the user is allowed to delete.
     *
     * @param  array<int, int|string>  $ids
     * @return int how many products were deleted
     */
    public function delete(User $user, array $ids): int
    {
        return $this->allowed($user, 'delete', $ids)
            ->each(fn (Product $product) => $product->delete())
            ->count();
    }

    /**
     * Change the status of the selected products the user is allowed to update.
     *
     * @param  array<int, int|string>  $ids
     * @return int how many products were updated
     */
    public function changeStatus(User $user, array $ids, ProductStatus $status): int
    {
        return DB::transaction(fn () => $this->allowed($user, 'update', $ids)
            ->reject(fn (Product $product) => $product->status === $status)
            ->each(fn (Product $product) => $product->update(['status' => $status]))
            ->count());
    }

    /**
     * Load the products one by one (so model events and the activity log run)
     * and keep only the ones the policy allows.
     *
     * @param  array<int, int|string>  $ids
     * @return Collection<int, Product>
     */
    private function allowed(User $user, string $ability, array $ids): Collection
    {
        return Product::query()
            ->whereKey($ids)
            ->get()
            ->filter(fn (Product $product) => $user->can($ability, $product));
    }
}