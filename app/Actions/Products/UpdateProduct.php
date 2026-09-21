<?php

namespace App\Actions\Products;

use App\Models\Product;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
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
                $product->fill($this->attributes($data));

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
}