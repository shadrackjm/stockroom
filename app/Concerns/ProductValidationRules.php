<?php

namespace App\Concerns;

use App\Enums\ProductStatus;
use App\Models\Category;
use App\Models\Product;
use App\Models\Tag;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Validation\Rule;

trait ProductValidationRules
{
    /**
     * Get the validation rules used to create or update a product.
     *
     * @return array<string, array<int, ValidationRule|array<mixed>|string>>
     */
    protected function productRules(?Product $product = null): array
    {
        $rules = [
            'name' => ['required', 'string', 'max:120'],
            'sku' => ['required', 'string', 'alpha_dash', 'max:32', Rule::unique(Product::class)->ignore($product)],
            'category_id' => ['nullable', 'integer', Rule::exists(Category::class, 'id')],
            'tag_ids' => ['array'],
            'tag_ids.*' => ['integer', Rule::exists(Tag::class, 'id')],
            'description' => ['nullable', 'string', 'max:5000'],
            'price' => ['required', 'regex:/^\d{1,7}(\.\d{1,2})?$/'],
            'stock' => ['required', 'integer', 'min:0', 'max:1000000'],
            'status' => ['required', Rule::enum(ProductStatus::class)],
            'image' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
            'remove_image' => ['boolean'],
        ];

        if ($product) {
            $rules['version'] = ['required', 'integer'];
        }

        return $rules;
    }

    /**
     * Friendlier messages for the rules above.
     *
     * @return array<string, string>
     */
    protected function productMessages(): array
    {
        return [
            'price.regex' => 'The price must be a number with at most 2 decimals, like 25 or 25.99.',
            'sku.alpha_dash' => 'The SKU may only contain letters, numbers, dashes and underscores.',
        ];
    }
}
