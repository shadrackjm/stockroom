{{-- Shared by the create and edit pages. Expects $form (ProductForm), $categories and $tags. --}}
<div class="grid gap-6 lg:grid-cols-3">
    <div class="space-y-6 lg:col-span-2">
        <flux:input wire:model="form.name" :label="__('Name')" required autofocus />

        <div class="grid gap-6 sm:grid-cols-2">
            <flux:input wire:model="form.sku" :label="__('SKU')"
                :description="__('Letters, numbers and dashes, e.g. HD-1042')" required />

            <flux:select wire:model="form.category_id" :label="__('Category')">
                <flux:select.option value="">{{ __('No category') }}</flux:select.option>
                @foreach ($categories as $category)
                    <flux:select.option :value="$category->id">{{ $category->name }}</flux:select.option>
                @endforeach
            </flux:select>
        </div>

        <div class="grid gap-6 sm:grid-cols-2">
            <flux:input wire:model="form.price" :label="__('Price')" icon="currency-dollar" inputmode="decimal"
                placeholder="0.00" required />

            <flux:input wire:model="form.stock" :label="__('Stock')" type="number" min="0" step="1" required />
        </div>

        <flux:textarea wire:model="form.description" :label="__('Description')" rows="5" />

        <flux:checkbox.group wire:model="form.tag_ids" :label="__('Tags')" variant="pills">
            @foreach ($tags as $tag)
                <flux:checkbox :value="(string) $tag->id" :label="$tag->name" />
            @endforeach
        </flux:checkbox.group>
    </div>

    <div class="space-y-6">
        <flux:radio.group wire:model="form.status" :label="__('Status')" variant="segmented">
            @foreach (\App\Enums\ProductStatus::cases() as $status)
                <flux:radio :value="$status->value" :label="$status->label()" />
            @endforeach
        </flux:radio.group>

        <flux:field>
            <flux:label>{{ __('Image') }}</flux:label>

            {{-- Preview: the new upload, or the current image, or a placeholder --}}
            <div
                class="mb-3 aspect-square overflow-hidden rounded-lg border border-zinc-200 bg-zinc-50 dark:border-zinc-700 dark:bg-zinc-900">
                @if ($form->image?->isPreviewable())
                    <img src="{{ $form->image->temporaryUrl() }}" alt="" class="size-full object-cover" />
                @elseif ($form->product?->image_url && !$form->remove_image)
                    <img src="{{ $form->product->image_url }}" alt="" class="size-full object-cover" />
                @else
                    <div class="flex size-full items-center justify-center">
                        <flux:icon.photo class="size-12 text-zinc-300 dark:text-zinc-600" />
                    </div>
                @endif
            </div>

            <flux:input type="file" wire:model="form.image" accept="image/png, image/jpeg, image/webp" />

            <div wire:loading wire:target="form.image" class="mt-2 text-sm text-zinc-500">{{ __('Uploading…') }}</div>

            <flux:error name="form.image" />

            @if ($form->product?->image_path && !$form->remove_image && !$form->image)
                <flux:button wire:click="$set('form.remove_image', true)" size="sm" variant="ghost" icon="x-mark"
                    class="mt-2">
                    {{ __('Remove image') }}
                </flux:button>
            @endif
        </flux:field>
    </div>
</div>