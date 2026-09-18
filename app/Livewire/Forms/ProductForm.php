<?php

namespace App\Livewire\Forms;

use App\Actions\Products\CreateProduct;
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
}
