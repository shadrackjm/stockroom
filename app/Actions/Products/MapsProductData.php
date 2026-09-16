<?php

namespace App\Actions\Products;

use App\Support\Money;
use Illuminate\Http\UploadedFile;

trait MapsProductData
{
    /**
     * Turn validated form input into database columns.
     *
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function attributes(array $data): array
    {
        return [
            'name' => $data['name'],
            'sku' => $data['sku'],
            'category_id' => $data['category_id'] ?: null,
            'description' => $data['description'] ?: null,
            'price_cents' => Money::toCents($data['price']),
            'stock' => (int) $data['stock'],
            'status' => $data['status'],
        ];
    }

    protected function storeImage(?UploadedFile $image): ?string
    {
        return $image?->store('products', 'public');
    }
}