<?php

use App\Models\Product;
use Livewire\Attributes\Title;
use Livewire\Component;
use Flux\Flux;

new #[Title('Product')]
    class extends Component {
    public Product $product;

    public function mount(Product $product): void
    {
        $this->product = $product->load(['category', 'user', 'tags']);
    }

    public function delete(): void
    {
        $this->authorize('delete', $this->product);

        $this->product->delete();

        Flux::toast(variant: 'success', text: "\"{$this->product->name}\" was moved to the trash.");

        $this->redirectRoute('products.index', navigate: true);
    }
};
?>

<div class="mx-auto w-full max-w-5xl">
    <flux:breadcrumbs class="mb-4">
        <flux:breadcrumbs.item :href="route('products.index')" wire:navigate>{{ __('Products') }}
        </flux:breadcrumbs.item>
        <flux:breadcrumbs.item>{{ $product->name }}</flux:breadcrumbs.item>
    </flux:breadcrumbs>

    <div class="mb-8 flex flex-wrap items-start justify-between gap-4">
        <div>
            <div class="flex items-center gap-3">
                <flux:heading size="xl" level="1">{{ $product->name }}</flux:heading>
                <flux:badge :color="$product->status->color()">{{ $product->status->label() }}</flux:badge>
            </div>
            <flux:text class="mt-1">
                {{ __('SKU :sku · added by :name', ['sku' => $product->sku, 'name' => $product->user->name]) }}
            </flux:text>
        </div>

        <div class="flex gap-2">
            @can('update', $product)
                <flux:button :href="route('products.edit', $product)" icon="pencil-square" wire:navigate>{{ __('Edit') }}
                </flux:button>
            @endcan
            @can('delete', $product)
                <flux:modal.trigger name="delete-product">
                    <flux:button variant="danger" icon="trash">{{ __('Delete') }}</flux:button>
                </flux:modal.trigger>
            @endcan
        </div>
    </div>

    <div class="grid gap-8 lg:grid-cols-3">
        <div class="space-y-6 lg:col-span-2">
            <div class="grid gap-6 sm:grid-cols-[14rem_1fr]">
                <div
                    class="aspect-square overflow-hidden rounded-lg border border-zinc-200 bg-zinc-50 dark:border-zinc-700 dark:bg-zinc-900">
                    @if ($product->image_url)
                        <img src="{{ $product->image_url }}" alt="{{ $product->name }}" class="size-full object-cover" />
                    @else
                        <div class="flex size-full items-center justify-center">
                            <flux:icon.photo class="size-12 text-zinc-300 dark:text-zinc-600" />
                        </div>
                    @endif
                </div>
            </div>

            <dl class="grid grid-cols-2 content-start gap-x-6 gap-y-4">
                <div>
                    <dt class="text-sm text-zinc-500">{{ __('Price') }}</dt>
                    <dd class="text-2xl font-semibold tabular-nums">{{ $product->formatted_price }}</dd>
                </div>
                <div>
                    <dt class="text-sm text-zinc-500">{{ __('In stock') }}</dt>
                    <dd class="text-2xl font-semibold tabular-nums">{{ $product->stock }}</dd>
                </div>
                <div>
                    <dt class="text-sm text-zinc-500">{{ __('Category') }}</dt>
                    <dd>{{ $product->category?->name ?? '—' }}</dd>
                </div>
                <div>
                    <dt class="text-sm text-zinc-500">{{ __('Tags') }}</dt>
                    <dd class="flex flex-wrap gap-1">
                        @forelse ($product->tags as $tag)
                            <flux:badge size="sm">{{ $tag->name }}</flux:badge>
                        @empty
                            —
                        @endforelse
                    </dd>
                </div>
            </dl>

            <div>
                <flux:heading size="lg" class="mb-2">{{ __('Description') }}</flux:heading>
                <flux:text class="whitespace-pre-line">{{ $product->description ?: __('No description yet.') }}
                </flux:text>
            </div>
        </div>
    </div>
    <flux:modal name="delete-product" class="max-w-md">
        <div class="space-y-6">
            <div>
                <flux:heading size="lg">{{ __('Delete this product?') }}</flux:heading>
                <flux:text class="mt-2">{{ __('It will be moved to the trash. You can restore it from there later.') }}
                </flux:text>
            </div>

            <div class="flex justify-end gap-2">
                <flux:modal.close>
                    <flux:button variant="ghost">{{ __('Cancel') }}</flux:button>
                </flux:modal.close>
                <flux:button wire:click="delete" variant="danger">{{ __('Move to trash') }}</flux:button>
            </div>
        </div>
    </flux:modal>
</div>