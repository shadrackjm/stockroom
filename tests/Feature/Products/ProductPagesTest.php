<?php

use App\Models\Category;
use App\Models\Product;
use App\Models\Tag;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Livewire\Livewire;

beforeEach(function () {
    $this->user = User::factory()->create();
    $this->actingAs($this->user);
});

test('the list does not run a query per row (no N+1)', function () {
    Product::factory(10)->hasAttached(Tag::factory(2))->create();

    DB::enableQueryLog();
    Livewire::test('pages::products.index');

    // users, products count, products page, categories (list + filter), tags — not 10+ per row.
    expect(count(DB::getQueryLog()))->toBeLessThan(10);
});

test('the list can be searched, filtered and sorted', function () {
    $mugs = Category::factory()->create();
    Product::factory()->for($mugs)->active()->create(['name' => 'Red Mug', 'price_cents' => 900]);
    Product::factory()->for($mugs)->active()->create(['name' => 'Blue Mug', 'price_cents' => 500]);
    Product::factory()->draft()->create(['name' => 'Desk Lamp']);

    Livewire::test('pages::products.index')
        ->set('search', 'mug')
        ->assertSee('Red Mug')->assertDontSee('Desk Lamp')
        ->call('sortBy', 'price')
        ->assertSeeInOrder(['Blue Mug', 'Red Mug'])
        ->call('sortBy', 'price')
        ->assertSeeInOrder(['Red Mug', 'Blue Mug'])
        ->call('clearFilters')
        ->set('status', 'draft')
        ->assertSee('Desk Lamp')->assertDontSee('Red Mug');
});

test('a stale edit form shows a conflict instead of overwriting', function () {
    $product = Product::factory()->for($this->user)->create(['price_cents' => 2000]);

    // I open the edit form...
    $myForm = Livewire::test('pages::products.edit', ['product' => $product]);

    // ...a colleague saves first...
    Livewire::test('pages::products.edit', ['product' => $product])
        ->set('form.price', '30')
        ->call('save');

    // ...and my save is stopped.
    $myForm->set('form.price', '99')
        ->call('save')
        ->assertHasErrors('version')
        ->call('loadLatest')
        ->assertHasNoErrors()
        ->assertSet('form.price', '30.00');

    expect($product->fresh()->price_cents)->toBe(3000);
});