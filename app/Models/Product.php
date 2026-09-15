<?php

namespace App\Models;

use App\Enums\ProductStatus;
use App\Observers\ProductObserver;
use App\Policies\ProductPolicy;
use App\Support\Money;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Attributes\RouteKey;
use Illuminate\Database\Eloquent\Attributes\UsePolicy;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Storage;

#[Fillable(['category_id', 'name', 'sku', 'description', 'price_cents', 'stock', 'status', 'image_path'])]
#[RouteKey('slug')]
#[ObservedBy(ProductObserver::class)]
#[UsePolicy(ProductPolicy::class)]
class Product extends Model
{
    /** @use HasFactory<\Database\Factories\ProductFactory> */
    use HasFactory, SoftDeletes;

    /**
     * Defaults for a new product, matching the migration's defaults.
     *
     * @var array<string, mixed>
     */
    protected $attributes = [
        'stock' => 0,
        'status' => 'draft',
        'version' => 1,
    ];

    protected function casts(): array
    {
        return [
            'status' => ProductStatus::class,
            'price_cents' => 'integer',
            'stock' => 'integer',
            'version' => 'integer',
        ];
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * @return BelongsTo<Category, $this>
     */
    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    /**
     * @return BelongsToMany<Tag, $this>
     */
    public function tags(): BelongsToMany
    {
        return $this->belongsToMany(Tag::class);
    }

    /**
     * "$25.00" — read it as $product->formatted_price.
     */
    protected function formattedPrice(): Attribute
    {
        return Attribute::get(fn() => Money::format($this->price_cents));
    }

    /**
     * The public URL of the image, or null — read it as $product->image_url.
     */
    protected function imageUrl(): Attribute
    {
        return Attribute::get(fn() => $this->image_path
            ? Storage::disk('public')->url($this->image_path)
            : null);
    }
}
