<?php

namespace App\Models;

use App\Enums\ProductStatus;
use App\Concerns\LogsActivity;
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
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;

#[Fillable(['category_id', 'name', 'sku', 'description', 'price_cents', 'stock', 'status', 'image_path'])]
#[RouteKey('slug')]
#[ObservedBy(ProductObserver::class)]
#[UsePolicy(ProductPolicy::class)]
class Product extends Model
{
    /** @use HasFactory<\Database\Factories\ProductFactory> */
    use HasFactory, LogsActivity, SoftDeletes;

    /**
     * The columns the product list can be sorted by: [url value => database column].
     */
    public const SORTABLE = [
        'name' => 'name',
        'price' => 'price_cents',
        'stock' => 'stock',
        'created' => 'created_at',
    ];

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

    /**
     * Search by name or SKU: Product::search('hoodie').
     */
    #[Scope]
    protected function search(Builder $query, ?string $term): void
    {
        $query->when($term, fn(Builder $query) => $query->where(
            fn(Builder $query) => $query
                ->whereLike('name', "%{$term}%")
                ->orWhereLike('sku', "%{$term}%")
        ));
    }

    /**
     * Apply everything the product list's filter bar can send.
     *
     * @param  array{search?: ?string, category?: mixed, status?: ?string, sort?: ?string, direction?: ?string}  $filters
     */
    #[Scope]
    protected function filter(Builder $query, array $filters): void
    {
        $column = self::SORTABLE[$filters['sort'] ?? ''] ?? 'created_at';
        $direction = ($filters['direction'] ?? '') === 'asc' ? 'asc' : 'desc';

        $query
            ->search($filters['search'] ?? null)
            ->when($filters['category'] ?? null, fn(Builder $query, $category) => $query->where('category_id', $category))
            ->when(ProductStatus::tryFrom($filters['status'] ?? ''), fn(Builder $query, $status) => $query->where('status', $status))
            ->orderBy($column, $direction)
            ->orderBy('id', $direction);
    }

    /**
     * How a field name reads in the activity log.
     */
    public function activityFieldLabel(string $field): string
    {
        return match ($field) {
            'price_cents' => 'price',
            'category_id' => 'category',
            'image_path' => 'image',
            'sku' => 'SKU',
            default => str_replace('_', ' ', $field),
        };
    }

    /**
     * How a stored value reads in the activity log.
     */
    public function activityFieldValue(string $field, mixed $value): string
    {
        if ($value === null || $value === '') {
            return 'empty';
        }

        return match ($field) {
            'price_cents' => Money::format((int) $value),
            'status' => ProductStatus::tryFrom($value)?->label() ?? $value,
            'category_id' => Category::find($value)?->name ?? 'a deleted category',
            'image_path' => 'a new image',
            'description' => '"' . str($value)->limit(30) . '"',
            default => (string) $value,
        };
    }
}
