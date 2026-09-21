<?php

namespace App\Actions\Products;

use App\Models\Product;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Throwable;

class UpdateProduct
{
    use MapsProductData;

    /**
     * Update a product, its tags and its image.
     *
     * @param  array<string, mixed>  $data  validated input
     */
    public function handle(Product $product, array $data): Product
    {
        $oldImage = $product->image_path;
        $newImage = $this->storeImage($data['image'] ?? null);
        $imageChanged = $newImage !== null || ($data['remove_image'] ?? false);

        try {
            DB::transaction(function () use ($product, $data, $newImage, $imageChanged) {
                $version = (int) $data['version'];

                // Optimistic locking in one statement:
                // UPDATE products SET version = 6 WHERE id = 1 AND version = 5
                // If someone saved first, the version is already 6 and 0 rows match.
                $claimed = Product::whereKey($product->getKey())
                    ->where('version', $version)
                    ->update(['version' => $version + 1]);

                if ($claimed === 0) {
                    throw ValidationException::withMessages([
                        'version' => $this->conflictMessage($product),
                    ]);
                }

                $product->fill($this->attributes($data));
                $product->version = $version + 1;

                if ($imageChanged) {
                    $product->image_path = $newImage;
                }

                $product->save();
                $product->tags()->sync($data['tag_ids'] ?? []);
            });
        } catch (Throwable $e) {
            if ($newImage) {
                Storage::disk('public')->delete($newImage);
            }

            throw $e;
        }

        // Only delete the old file once the new data is safely committed.
        if ($imageChanged && $oldImage) {
            Storage::disk('public')->delete($oldImage);
        }

        return $product;
    }

    private function conflictMessage(Product $product): string
    {
        $editor = $product->activities()->with('user')->first()?->user?->name ?? 'Someone else';

        return "{$editor} changed this product while you were editing it. "
            . 'Your changes were not saved — reload to see the latest version.';
    }
}