<?php

namespace App\Actions\Products;

use App\Models\Product;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Throwable;

class CreateProduct
{
    use MapsProductData;

    /**
     * Create a product (with its tags and image) for the given user.
     *
     * @param  array<string, mixed>  $data  validated input
     */
    public function handle(User $user, array $data): Product
    {
        $imagePath = $this->storeImage($data['image'] ?? null);

        try {
            return DB::transaction(function () use ($user, $data, $imagePath) {
                $product = $user->products()->create([
                    ...$this->attributes($data),
                    'image_path' => $imagePath,
                ]);

                $product->tags()->sync($data['tag_ids'] ?? []);

                return $product;
            });
        } catch (Throwable $e) {
            // The database rolled back, so the uploaded file would be an orphan.
            if ($imagePath) {
                Storage::disk('public')->delete($imagePath);
            }

            throw $e;
        }
    }
}
