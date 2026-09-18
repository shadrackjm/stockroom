<?php

use App\Livewire\Forms\ProductForm;
use App\Models\Category;
use App\Models\Product;
use App\Models\Tag;
use Flux\Flux;
use Livewire\Component;
use Livewire\Attributes\Title;
use Livewire\WithFileUploads;

new #[Title('New product')] class extends Component
{
    use WithFileUploads;

    public ProductForm $form;

    public function mount(){
        $this->authorize('create',Product::class);
    }

    public function save()
    {
        $this->authorize('create', Product::class);

        $product = $this->form->store();

        Flux::toast(variant: 'success', text: "\"{$product->name}\" was created.");

        $this->redirectRoute('products.show',$product, navigate: true);
    }

    public function with(): array
    {
        return [
            'categories' => Category::orderBy('name')->get(),
            'tags' => Tag::orderBy('name')->get(),
        ];
    }
};
?>

<div class="mx-auto w-full max-w-5xl">
    <flux:breadcrumbs class="mb-4">
        <flux:breadcrumbs.item :href="route('products.index')" wire:navigate>{{ __('Products') }}</flux:breadcrumbs.item>
        <flux:breadcrumbs.item>{{ __('New') }}</flux:breadcrumbs.item>
    </flux:breadcrumbs>
    
    <flux:heading size="xl" level="1" class="mb-6">{{ __('New product') }}</flux:heading>

    <form wire:submit="save" class="space-y-6">
        @include('pages.products.partials.form-fields')

        <div class="flex items-center justify-end gap-3 border-t border-zinc-200 pt-6 dark:border-zinc-700">
            <flux:button :href="route('products.index')" variant="ghost" wire:navigate>{{ __('Cancel') }}</flux:button>
            <flux:button type="submit" variant="primary">{{ __('Create product') }}</flux:button>
        </div>
    </form>
</div>