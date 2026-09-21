<?php

use App\Livewire\Forms\ProductForm;
use App\Models\Category;
use App\Models\Product;
use App\Models\Tag;
use Flux\Flux;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithFileUploads;

new #[Title('Edit product')]
    class extends Component {
    use WithFileUploads;

    public ProductForm $form;

    public function mount(Product $product): void
    {
        $this->authorize('update', $product);

        $this->form->setProduct($product);
    }

    public function save(): void
    {
        $this->authorize('update', $this->form->product);

        $product = $this->form->update();

        Flux::toast(variant: 'success', text: "\"{$product->name}\" was updated.");

        $this->redirectRoute('products.show', $product, navigate: true);
    }

    public function with(): array
    {
        return [
            'categories' => Category::orderBy('name')->get(),
            'tags' => Tag::orderBy('name')->get(),
        ];
    }

    /**
     * After a conflict: throw away my edits and load what's in the database now.
     */
    public function loadLatest(): void
    {
        $this->form->setProduct($this->form->product->fresh());

        $this->resetErrorBag();
    }
};
?>

<div class="mx-auto w-full max-w-5xl">
    <flux:breadcrumbs class="mb-4">
        <flux:breadcrumbs.item :href="route('products.index')" wire:navigate>{{ __('Products') }}
        </flux:breadcrumbs.item>
        <flux:breadcrumbs.item :href="route('products.show', $form->product)" wire:navigate>{{ $form->product->name }}
        </flux:breadcrumbs.item>
        <flux:breadcrumbs.item>{{ __('Edit') }}</flux:breadcrumbs.item>
    </flux:breadcrumbs>

    <flux:heading size="xl" level="1" class="mb-6">{{ __('Edit :name', ['name' => $form->product->name]) }}
    </flux:heading>

    {{-- Optimistic locking: someone else saved this product after we opened the form --}}
    @error('version')
        <flux:callout variant="danger" icon="exclamation-triangle" class="mb-6">
            <flux:callout.heading>{{ __('This product was changed by someone else') }}</flux:callout.heading>
            <flux:callout.text>{{ $message }}</flux:callout.text>
            <x-slot name="actions">
                <flux:button wire:click="loadLatest" size="sm">{{ __('Load the latest version') }}</flux:button>
            </x-slot>
        </flux:callout>
    @enderror

    <form wire:submit="save" class="space-y-6">
        @include('pages.products.partials.form-fields')

        <div class="flex items-center justify-end gap-3 border-t border-zinc-200 pt-6 dark:border-zinc-700">
            <flux:text wire:dirty wire:target="form" class="me-auto">{{ __('You have unsaved changes.') }}</flux:text>

            <flux:button :href="route('products.show', $form->product)" variant="ghost" wire:navigate>{{ __('Cancel') }}
            </flux:button>
            <flux:button type="submit" variant="primary">{{ __('Save changes') }}</flux:button>
        </div>
    </form>
</div>