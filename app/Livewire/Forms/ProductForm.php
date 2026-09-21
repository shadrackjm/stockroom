<?php

namespace App\Livewire\Forms;

use App\Actions\Products\CreateProduct;
use App\Actions\Products\UpdateProduct;
use App\Support\Money;
use App\Concerns\ProductValidationRules;
use App\Models\Product;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Locked;
use Livewire\Attributes\Validate;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;
use Livewire\Form;

class ProductForm extends Form
{
    use ProductValidationRules;

    #[Locked]
    public ?Product $product = null;

    public string $name = '';

    public string $sku = '';

    public string $category_id = '';

    /** @var array<int, string> */
    public array $tag_ids = [];

    public string $description = '';

    public string $price = '';

    public string $stock = '0';

    public string $status = 'draft';

    /** @var TemporaryUploadedFile|null */
    public $image = null;

    public bool $remove_image = false;

    /**
     * @return array<string, mixed>
     */
    protected function rules(): array
    {
        return $this->productRules($this->product);
    }

    /**
     * @return array<string, string>
     */
    protected function messages(): array
    {
        return $this->productMessages();
    }

    public function store(): Product
    {
        $product = app(CreateProduct::class)->handle(Auth::user(), $this->validate());

        $this->reset();

        return $product;
    }

    /**
     * Fill the form from an existing product.
     */
    public function setProduct(Product $product): void
    {
        $this->product = $product;

        $this->name = $product->name;
        $this->sku = $product->sku;
        $this->category_id = (string) $product->category_id;
        $this->tag_ids = $product->tags()->pluck('tags.id')->map(fn($id) => (string) $id)->all();
        $this->description = (string) $product->description;
        $this->price = Money::fromCents($product->price_cents);
        $this->stock = (string) $product->stock;
        $this->status = $product->status->value;
        $this->image = null;
        $this->remove_image = false;
    }

    public function update(): Product
    {
        return app(UpdateProduct::class)->handle($this->product, $this->validate());
    }
}
