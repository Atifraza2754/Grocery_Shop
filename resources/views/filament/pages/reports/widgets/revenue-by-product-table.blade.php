<x-filament-widgets::widget class="fi-wi-table">
    <x-filament::section>
        <div class="flex flex-col gap-4">

            {{-- ===== Header: title + product dropdown ===== --}}
            <div class="flex flex-col gap-3 lg:flex-row lg:items-start lg:justify-between">

                {{-- Title + product dropdown --}}
                <div class="flex flex-col gap-2">
                    <h3 class="text-base font-semibold leading-6 text-gray-950 dark:text-white">
                        Revenue by product
                    </h3>
                    <div class="flex items-center gap-2">
                        <x-filament::input.wrapper>
                            <x-filament::input.select wire:model.live="productId">
                                <option value="">All products (top 100 by orders)</option>
                                @foreach ($this->getProductOptions() as $id => $label)
                                    <option value="{{ $id }}">{{ $label }}</option>
                                @endforeach
                            </x-filament::input.select>
                        </x-filament::input.wrapper>
                    </div>
                </div>
            </div>

            {{-- ===== Result table ===== --}}
            {{ $this->table }}
        </div>
    </x-filament::section>
</x-filament-widgets::widget>
