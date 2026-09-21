<?php

use App\Actions\Products\BulkProductAction;
use App\Actions\Products\CreateProduct;
use App\Actions\Products\UpdateProduct;
use App\Enums\ProductStatus;
use App\Models\Product;
use App\Models\User;
use App\Support\Money;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

/**
 * Validated input as it arrives from any form (Livewire, React, Vue or Svelte).
 */
function productInput(array $overrides = []): array
{
    return [
        'name' => 'Blue Hoodie',
        'sku' => 'BH-001',
        'category_id' => null,
        'tag_ids' => [],
        'description' => 'Warm and soft.',
        'price' => '20.00',
        'stock' => '5',
        'status' => 'active',
        ...$overrides,
    ];
}

test('money is converted to and from cents without float errors', function () {
    expect(Money::toCents('25'))->toBe(2500)
        ->and(Money::toCents('25.5'))->toBe(2550)
        ->and(Money::toCents('0.10'))->toBe(10)
        ->and(Money::toCents('19.99'))->toBe(1999)
        ->and(Money::fromCents(2550))->toBe('25.50')
        ->and(Money::format(123456))->toBe('$1,234.56');
});

test('a product gets a unique slug from its name', function () {
    $user = User::factory()->create();

    $first = app(CreateProduct::class)->handle($user, productInput());
    $second = app(CreateProduct::class)->handle($user, productInput(['sku' => 'BH-002']));

    expect($first->slug)->toBe('blue-hoodie')
        ->and($second->slug)->toBe('blue-hoodie-2')
        ->and($first->price_cents)->toBe(2000);
});

test('replacing an image deletes the old file', function () {
    Storage::fake('public');
    $user = User::factory()->create();
    $product = app(CreateProduct::class)->handle($user, productInput(['image' => UploadedFile::fake()->image('old.jpg')]));
    $oldPath = $product->image_path;

    app(UpdateProduct::class)->handle($product, productInput([
        'image' => UploadedFile::fake()->image('new.jpg'),
        'version' => $product->fresh()->version,
    ]));

    Storage::disk('public')->assertMissing($oldPath);
    Storage::disk('public')->assertExists($product->fresh()->image_path);
});

test('the activity log records who changed which field', function () {
    $user = User::factory()->create(['name' => 'Shadrack']);
    $this->actingAs($user);

    $product = app(CreateProduct::class)->handle($user, productInput());
    app(UpdateProduct::class)->handle($product, productInput(['price' => '25', 'version' => 1]));

    $latest = $product->activities()->first();

    expect($latest->description())->toBe('Shadrack updated price from $20.00 to $25.00')
        ->and($product->activities)->toHaveCount(2);
});

test('saving a stale form is rejected (optimistic locking)', function () {
    $user = User::factory()->create();
    $product = app(CreateProduct::class)->handle($user, productInput());

    // Two people open the edit form while the product is at version 1.
    // The first one saves...
    app(UpdateProduct::class)->handle(Product::find($product->id), productInput(['price' => '30', 'version' => 1]));

    // ...then the second one saves their old copy.
    $save = fn () => app(UpdateProduct::class)->handle(Product::find($product->id), productInput(['price' => '99', 'version' => 1]));

    expect($save)->toThrow(ValidationException::class);
    expect($product->fresh())->price_cents->toBe(3000)->version->toBe(2);
});

test('only the owner or an admin may change a product', function () {
    $owner = User::factory()->create();
    $stranger = User::factory()->create();
    $admin = User::factory()->create(['is_admin' => true]);
    $product = Product::factory()->for($owner)->create();

    expect($owner->can('update', $product))->toBeTrue()
        ->and($admin->can('update', $product))->toBeTrue()
        ->and($stranger->can('update', $product))->toBeFalse()
        ->and($stranger->can('view', $product))->toBeTrue();
});

test('bulk actions skip products the user may not touch', function () {
    $user = User::factory()->create();
    $mine = Product::factory(2)->for($user)->draft()->create();
    $theirs = Product::factory()->draft()->create();
    $ids = [...$mine->pluck('id'), $theirs->id];

    expect(app(BulkProductAction::class)->changeStatus($user, $ids, ProductStatus::Active))->toBe(2)
        ->and($theirs->fresh()->status)->toBe(ProductStatus::Draft)
        ->and(app(BulkProductAction::class)->delete($user, $ids))->toBe(2)
        ->and(Product::count())->toBe(1);
});

test('the csv export respects filters and escapes formulas', function () {
    $user = User::factory()->create();
    Product::factory()->active()->create(['name' => '=HYPERLINK("http://evil.test")', 'sku' => 'EVIL-1']);
    Product::factory()->draft()->create(['sku' => 'DRAFT-1']);

    $response = $this->actingAs($user)->get(route('products.export', ['status' => 'active']));

    $response->assertOk()->assertDownload();
    $csv = $response->streamedContent();

    expect($csv)->toContain('EVIL-1')
        ->toContain("'=HYPERLINK")
        ->not->toContain('DRAFT-1');
});

test('guests are redirected to the login page', function (string $route) {
    $this->get(route($route))->assertRedirect(route('login'));
})->with(['products.index', 'products.create', 'products.trash', 'products.export']);