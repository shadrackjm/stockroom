<?php

namespace Database\Factories;

use App\Enums\ProductStatus;
use App\Models\Category;
use App\Models\Product;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Product>
 */
class ProductFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $name = fake()->randomElement(['Classic', 'Everyday', 'Premium', 'Compact', 'Vintage', 'Ultra', 'Organic', 'Travel', 'Smart', 'Deluxe', 'Mini', 'Pro'])
            . ' ' . fake()->randomElement(['Blue', 'Black', 'Walnut', 'Sage', 'Coral', 'Graphite', 'Ivory', 'Crimson', 'Sand', 'Ocean'])
            . ' ' . fake()->randomElement(['Hoodie', 'Backpack', 'Desk Lamp', 'Water Bottle', 'Headphones', 'Notebook', 'Mug', 'Sneakers', 'Keyboard', 'Candle', 'Yoga Mat', 'Wallet', 'Speaker', 'Plant Pot', 'Jacket']);

        return [
            'user_id' => User::factory(),
            'category_id' => Category::factory(),
            'name' => $name,
            // The observer makes slugs, but seeders run without model events.
            'slug' => Str::slug($name) . '-' . Str::lower(Str::random(5)),
            'sku' => Str::upper(fake()->unique()->bothify('??-#####')),
            'description' => fake()->paragraph(),
            'price_cents' => fake()->numberBetween(199, 49_999),
            'stock' => fake()->numberBetween(0, 250),
            'status' => fake()->randomElement([ProductStatus::Active, ProductStatus::Active, ProductStatus::Draft, ProductStatus::Archived]),
        ];
    }

    public function active(): static
    {
        return $this->state(fn () => ['status' => ProductStatus::Active]);
    }

    public function draft(): static
    {
        return $this->state(fn () => ['status' => ProductStatus::Draft]);
    }
}
