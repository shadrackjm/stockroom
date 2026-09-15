<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Product;
use App\Models\Tag;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $admin = User::factory()->create([
            'name' => 'Test User',
            'email' => 'test@example.com',
            'is_admin' => true,
        ]);

        // A second admin, so two people can edit the same product (the conflict demo).
        $editor = User::factory()->create([
            'name' => 'Jane Manager',
            'email' => 'jane@example.com',
            'is_admin' => true,
        ]);

        // A normal user who owns nothing: used to demo 403s and trash scoping.
        User::factory()->create([
            'name' => 'Sam Viewer',
            'email' => 'sam@example.com',
        ]);

        $categories = collect(['Apparel', 'Electronics', 'Home & Kitchen', 'Books', 'Toys', 'Sports', 'Beauty', 'Office'])
            ->map(fn(string $name) => Category::create(['name' => $name, 'slug' => Str::slug($name)]));

        $tags = collect(['New', 'Sale', 'Bestseller', 'Eco-friendly', 'Limited', 'Imported', 'Handmade', 'Gift Idea'])
            ->map(fn(string $name) => Tag::create(['name' => $name, 'slug' => Str::slug($name)]));

        Product::factory(500)
            ->recycle([$admin, $editor])
            ->recycle($categories)
            ->create()
            ->each(fn(Product $product) => $product->tags()->attach(
                $tags->random(fake()->numberBetween(0, 3))->pluck('id')
            ));
    }
}
