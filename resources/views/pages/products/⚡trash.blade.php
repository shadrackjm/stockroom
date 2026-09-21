<?php

use App\Models\Product;
use Flux\Flux;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithPagination;

new #[Title('Trash')] class extends Component {
    use WithPagination;

    public ?int $deletingId = null;

    #[Computed]
    public function products()
    {
        $user = Auth::user();

        return Product::onlyTrashed()
            ->with('user')
            // Admins see everyone's trash; everybody else only sees their own.
            ->unless($user->is_admin, fn ($query) => $query->whereBelongsTo($user))
            ->latest('deleted_at')
            ->paginate(10);
    }

    public function restore(int $id): void
    {
        $product = Product::onlyTrashed()->findOrFail($id);

        $this->authorize('restore', $product);

        $product->restore();

        Flux::toast(variant: 'success', text: "\"{$product->name}\" was restored.");
    }

    public function confirmForceDelete(int $id): void
    {
        $this->deletingId = $id;

        Flux::modal('force-delete')->show();
    }

    public function forceDelete(): void
    {
        $product = Product::onlyTrashed()->findOrFail($this->deletingId);

        $this->authorize('forceDelete', $product);

        $product->forceDelete();

        $this->deletingId = null;
        Flux::modal('force-delete')->close();
        Flux::toast(variant: 'success', text: "\"{$product->name}\" was permanently deleted.");
    }
}; ?>

<div class="flex flex-col gap-6">
    <div>
        <flux:heading size="xl" level="1">{{ __('Trash') }}</flux:heading>
        <flux:text class="mt-1">{{ __('Deleted products stay here until you restore them or delete them for good.') }}</flux:text>
    </div>

    <flux:table :paginate="$this->products">
        <flux:table.columns>
            <flux:table.column>{{ __('Product') }}</flux:table.column>
            <flux:table.column>{{ __('Owner') }}</flux:table.column>
            <flux:table.column>{{ __('Deleted') }}</flux:table.column>
            <flux:table.column></flux:table.column>
        </flux:table.columns>

        <flux:table.rows>
            @forelse ($this->products as $product)
                <flux:table.row wire:key="trashed-{{ $product->id }}">
                    <flux:table.cell>
                        <div class="font-medium text-zinc-800 dark:text-white">{{ $product->name }}</div>
                        <div class="text-xs text-zinc-500">{{ $product->sku }}</div>
                    </flux:table.cell>
                    <flux:table.cell>{{ $product->user->name }}</flux:table.cell>
                    <flux:table.cell>{{ $product->deleted_at->diffForHumans() }}</flux:table.cell>
                    <flux:table.cell align="end">
                        <div class="flex justify-end gap-2">
                            <flux:button wire:click="restore({{ $product->id }})" size="sm" icon="arrow-uturn-left">{{ __('Restore') }}</flux:button>
                            <flux:button wire:click="confirmForceDelete({{ $product->id }})" size="sm" variant="danger" icon="trash">{{ __('Delete forever') }}</flux:button>
                        </div>
                    </flux:table.cell>
                </flux:table.row>
            @empty
                <flux:table.row>
                    <flux:table.cell colspan="4">
                        <div class="flex flex-col items-center gap-2 py-12 text-center">
                            <flux:icon.trash class="size-10 text-zinc-400" />
                            <flux:heading>{{ __('The trash is empty') }}</flux:heading>
                        </div>
                    </flux:table.cell>
                </flux:table.row>
            @endforelse
        </flux:table.rows>
    </flux:table>

    <flux:modal name="force-delete" class="max-w-md">
        <div class="space-y-6">
            <div>
                <flux:heading size="lg">{{ __('Delete forever?') }}</flux:heading>
                <flux:text class="mt-2">{{ __('The product, its image and its activity history will be gone for good. This cannot be undone.') }}</flux:text>
            </div>

            <div class="flex justify-end gap-2">
                <flux:modal.close>
                    <flux:button variant="ghost">{{ __('Cancel') }}</flux:button>
                </flux:modal.close>
                <flux:button wire:click="forceDelete" variant="danger">{{ __('Delete forever') }}</flux:button>
            </div>
        </div>
    </flux:modal>
</div>