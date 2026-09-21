<?php

namespace App\Observers;

use App\Models\Product;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class ProductObserver
{
    public function creating(Product $product): void
    {
        $product->slug ??= $this->uniqueSlug($product->name);
    }

    private function uniqueSlug(string $name): string
    {
        $base = Str::slug($name);
        $slug = $base;
        $counter = 2;

        while (Product::withTrashed()->where('slug', $slug)->exists()) {
            $slug = "{$base}-{$counter}";
            $counter++;
        }

        return $slug;
    }

    /**
     * Soft-deleted products keep their image (they can be restored).
     * Permanently deleted ones don't need it any more.
     */
    public function forceDeleted(Product $product): void
    {
        if ($product->image_path) {
            Storage::disk('public')->delete($product->image_path);
        }
    }

    public function updating(Product $product): void
    {
        if (! $product->isDirty('version')) {
            $product->version = $product->getOriginal('version') + 1;
        }
    }

    
}
