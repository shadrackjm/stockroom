<?php

use App\Models\Product;
use Livewire\Component;
use Livewire\Attributes\Title;
use Livewire\WithPagination;
use Livewire\Attributes\Computed;
use App\Enums\ProductStatus;
use App\Models\Category;
use Livewire\Attributes\Url;
use App\Actions\Products\BulkProductAction;
use Flux\Flux;
use Illuminate\Support\Facades\Auth;


new #[Title('Products')]
    class extends Component {
    use WithPagination;

    // #[Url] keeps each filter in the address bar, so a filtered list can be bookmarked or shared.
    #[Url(except: '')]
    public string $search = '';

    #[Url(except: '')]
    public string $category = '';

    #[Url(except: '')]
    public string $status = '';

    #[Url(except: 'created')]
    public string $sort = 'created';

    #[Url(except: 'desc')]
    public string $direction = 'desc';

    /** @var array<int, string> IDs of the ticked rows */

    public array $selected = [];

    public ?int $deletingId = null;

    /**
     * Any filter change: go back to page 1 and forget the ticked rows.
     */
    public function updated(string $property): void
    {
        if (in_array($property, ['search', 'category', 'status'])) {
            $this->resetPage();
            $this->selected = [];
        }
    }

    public function sortBy(string $column): void
    {
        if (!array_key_exists($column, Product::SORTABLE)) {
            return;
        }

        $this->direction = $this->sort === $column && $this->direction === 'asc' ? 'desc' : 'asc';
        $this->sort = $column;
        $this->resetPage();
    }

    public function clearFilters(): void
    {
        $this->reset('search', 'category', 'status', 'selected');
        $this->resetPage();
    }

    #[Computed]
    public function products()
    {
        return Product::query()
            ->with(['category', 'user', 'tags'])
            ->filter($this->filters())
            ->paginate(10);
    }

    #[Computed]
    public function categories()
    {
        return Category::orderBy('name')->get();
    }

    private function filters(): array
    {
        return $this->only(['search', 'category', 'status', 'sort', 'direction']);
    }

    public function confirmDelete(int $id): void
    {
        $this->deletingId = $id;

        Flux::modal('delete-product')->show();
    }

    public function delete(): void
    {
        $product = Product::findOrFail($this->deletingId);

        $this->authorize('delete', $product);

        $product->delete();

        $this->deletingId = null;
        Flux::modal('delete-product')->close();
        Flux::toast(variant: 'success', text: "\"{$product->name}\" was moved to the trash.");
    }

    public function deleteSelected(BulkProductAction $bulk): void
    {
        $count = $bulk->delete(Auth::user(), $this->selected);

        $this->selected = [];
        Flux::modal('delete-selected')->close();
        Flux::toast(variant: 'success', text: trans_choice('{0} You can\'t delete any of those products.|{1} 1 product moved to the trash.|[2,*] :count products moved to the trash.', $count));
    }

    public function changeStatusOfSelected(string $status, BulkProductAction $bulk): void
    {
        $count = $bulk->changeStatus(Auth::user(), $this->selected, ProductStatus::from($status));

        $this->selected = [];
        Flux::toast(variant: 'success', text: trans_choice('{0} No products were changed.|{1} 1 product updated.|[2,*] :count products updated.', $count));
    }

    #[Computed]
    public function exportUrl(): string
    {
        return route('products.export', array_filter($this->filters()));
    }
};
?>

<div>
    <div class="flex flex-col gap-6">
        {{-- Header --}}
        <div class="flex flex-wrap items-end justify-between gap-4">
            <div>
                <flux:heading size="xl" level="1">{{ __('Products') }}</flux:heading>
                <flux:text class="mt-1">
                    {{ trans_choice('{0} No products found|{1} 1 product|[2,*] :count products', $this->products->total()) }}
                </flux:text>
            </div>

            

            <div class="flex gap-2">
                <flux:button :href="$this->exportUrl" icon="arrow-down-tray">{{ __('Export CSV') }}</flux:button>
                <flux:button :href="route('products.create')" variant="primary" icon="plus" wire:navigate>
                    {{ __('New product') }}
                </flux:button>
            </div>
        </div>

        {{-- Filters --}}
        <div class="grid gap-3 sm:grid-cols-[1fr_12rem_12rem_auto]">
            <flux:input wire:model.live.debounce.300ms="search" icon="magnifying-glass"
                :placeholder="__('Search by name or SKU…')" clearable />

            <flux:select wire:model.live="category">
                <flux:select.option value="">{{ __('All categories') }}</flux:select.option>
                @foreach ($this->categories as $option)
                    <flux:select.option :value="$option->id">{{ $option->name }}</flux:select.option>
                @endforeach
            </flux:select>

            <flux:select wire:model.live="status">
                <flux:select.option value="">{{ __('All statuses') }}</flux:select.option>
                @foreach (ProductStatus::cases() as $option)
                    <flux:select.option :value="$option->value">{{ $option->label() }}</flux:select.option>
                @endforeach
            </flux:select>

            @if ($search || $category || $status)
                <flux:button wire:click="clearFilters" variant="ghost" icon="x-mark">{{ __('Clear') }}</flux:button>
            @endif
        </div>

        {{-- Bulk actions: only visible once at least one row is ticked --}}
        @if (count($selected))
            <div
                class="flex flex-wrap items-center gap-3 rounded-lg border border-zinc-200 bg-zinc-50 px-4 py-2 dark:border-zinc-700 dark:bg-zinc-900">
                <flux:text class="font-medium">{{ trans_choice('{1} 1 selected|[2,*] :count selected', count($selected)) }}
                </flux:text>

                <flux:spacer />

                <flux:dropdown>
                    <flux:button size="sm" icon:trailing="chevron-down">{{ __('Change status') }}</flux:button>

                    <flux:menu>
                        @foreach (ProductStatus::cases() as $option)
                            <flux:menu.item wire:click="changeStatusOfSelected('{{ $option->value }}')">{{ $option->label() }}
                            </flux:menu.item>
                        @endforeach
                    </flux:menu>
                </flux:dropdown>

                <flux:modal.trigger name="delete-selected">
                    <flux:button size="sm" variant="danger" icon="trash" data-test="delete-selected">{{ __('Delete') }}
                    </flux:button>
                </flux:modal.trigger>
            </div>
        @endif

        {{-- Table --}}
        <flux:checkbox.group wire:model.live="selected">
            <flux:table :paginate="$this->products" wire:loading.class="opacity-60"
                wire:target="search, category, status, sortBy, gotoPage, nextPage, previousPage">
                <flux:table.columns>
                    <flux:table.column class="w-8">
                        <flux:checkbox.all />
                    </flux:table.column>
                    <flux:table.column sortable :sorted="$sort === 'name'" :direction="$direction"
                        wire:click="sortBy('name')">{{ __('Product') }}</flux:table.column>
                    <flux:table.column>{{ __('Category') }}</flux:table.column>
                    <flux:table.column>{{ __('Status') }}</flux:table.column>
                    <flux:table.column sortable :sorted="$sort === 'price'" :direction="$direction"
                        wire:click="sortBy('price')" align="end">{{ __('Price') }}</flux:table.column>
                    <flux:table.column sortable :sorted="$sort === 'stock'" :direction="$direction"
                        wire:click="sortBy('stock')" align="end">{{ __('Stock') }}</flux:table.column>
                    <flux:table.column>{{ __('Owner') }}</flux:table.column>
                    <flux:table.column></flux:table.column>
                </flux:table.columns>
                <flux:table.rows>
                    @forelse ($this->products as $product)
                        <flux:table.row wire:key="product-{{ $product->id }}">
                            <flux:table.cell>
                                <flux:checkbox :value="(string) $product->id" />
                            </flux:table.cell>

                            <flux:table.cell>
                                <a href="{{ route('products.show', $product) }}" wire:navigate
                                    class="flex items-center gap-3">
                                    @if ($product->image_url)
                                        <img src="{{ $product->image_url }}" alt="" class="size-10 rounded-md object-cover" />
                                    @else
                                        <div
                                            class="flex size-10 items-center justify-center rounded-md bg-zinc-100 dark:bg-zinc-700">
                                            <flux:icon.photo variant="mini" class="text-zinc-400" />
                                        </div>
                                    @endif

                                    <div>
                                        <div class="font-medium text-zinc-800 dark:text-white">{{ $product->name }}</div>
                                        <div class="text-xs text-zinc-500">{{ $product->sku }}</div>
                                    </div>
                                </a>
                            </flux:table.cell>

                            <flux:table.cell>
                                <div>{{ $product->category?->name ?? '—' }}</div>
                                <div class="mt-1 flex gap-1">
                                    @foreach ($product->tags as $tag)
                                        <flux:badge size="sm">{{ $tag->name }}</flux:badge>
                                    @endforeach
                                </div>
                            </flux:table.cell>

                            <flux:table.cell>
                                <flux:badge size="sm" :color="$product->status->color()" inset="top bottom">
                                    {{ $product->status->label() }}
                                </flux:badge>
                            </flux:table.cell>

                            <flux:table.cell align="end" class="tabular-nums">{{ $product->formatted_price }}
                            </flux:table.cell>

                            <flux:table.cell align="end" class="tabular-nums">
                                @if ($product->stock === 0)
                                    <flux:badge size="sm" color="red" inset="top bottom">{{ __('Out of stock') }}</flux:badge>
                                @else
                                    {{ $product->stock }}
                                @endif
                            </flux:table.cell>

                            <flux:table.cell>{{ $product->user->name }}</flux:table.cell>

                            <flux:table.cell align="end">
                                <flux:dropdown position="bottom" align="end">
                                    <flux:button variant="ghost" size="sm" icon="ellipsis-horizontal" inset="top bottom"
                                        :aria-label="__('Actions for :name', ['name' => $product->name])" />

                                    <flux:menu>
                                        <flux:menu.item :href="route('products.show', $product)" icon="eye" wire:navigate>
                                            {{ __('View') }}
                                        </flux:menu.item>

                                        @can('update', $product)
                                            <flux:menu.item :href="route('products.edit', $product)" icon="pencil-square"
                                                wire:navigate>{{ __('Edit') }}</flux:menu.item>
                                        @endcan
                                        @can('delete', $product)
                                            <flux:menu.separator />
                                            <flux:menu.item wire:click="confirmDelete({{ $product->id }})" icon="trash"
                                                variant="danger">{{ __('Delete') }}</flux:menu.item>
                                        @endcan
                                    </flux:menu>
                                </flux:dropdown>
                            </flux:table.cell>
                        </flux:table.row>
                    @empty
                        <flux:table.row>
                            <flux:table.cell colspan="8">
                                <div class="flex flex-col items-center gap-3 py-12 text-center">
                                    <flux:icon.cube class="size-10 text-zinc-400" />
                                    <flux:heading>{{ __('No products match your filters') }}</flux:heading>
                                    @if ($search || $category || $status)
                                        <flux:button wire:click="clearFilters" size="sm">{{ __('Clear filters') }}</flux:button>
                                    @else
                                        <flux:button :href="route('products.create')" size="sm" variant="primary" wire:navigate>
                                            {{ __('Create your first product') }}
                                        </flux:button>
                                    @endif
                                </div>
                            </flux:table.cell>
                        </flux:table.row>
                    @endforelse
                </flux:table.rows>
            </flux:table>
        </flux:checkbox.group>
    </div>
    {{-- Delete one product --}}
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
                <flux:button wire:click="delete" variant="danger" data-test="confirm-delete-product">
                    {{ __('Move to trash') }}
                </flux:button>
            </div>
        </div>
    </flux:modal>
    {{-- Delete the ticked products --}}
    <flux:modal name="delete-selected" class="max-w-md">
        <div class="space-y-6">
            <div>
                <flux:heading size="lg">
                    {{ trans_choice('{1} Delete 1 product?|[2,*] Delete :count products?', count($selected)) }}
                </flux:heading>
                <flux:text class="mt-2">{{ __('Products you are not allowed to delete will be skipped.') }}</flux:text>
            </div>

            <div class="flex justify-end gap-2">
                <flux:modal.close>
                    <flux:button variant="ghost">{{ __('Cancel') }}</flux:button>
                </flux:modal.close>
                <flux:button wire:click="deleteSelected" variant="danger" data-test="confirm-delete-selected">
                    {{ __('Move to trash') }}
                </flux:button>
            </div>
        </div>
    </flux:modal>
</div>