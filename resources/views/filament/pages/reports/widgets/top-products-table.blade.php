<x-filament-widgets::widget class="fi-wi-table">
    <x-filament::section>
        <div class="flex flex-col gap-4">

            {{-- ===== Header: title + area dropdown ===== --}}
            <div class="flex flex-col gap-3 lg:flex-row lg:items-start lg:justify-between">

                {{-- Title + area dropdown --}}
                <div class="flex flex-col gap-2">
                    <h3 class="text-base font-semibold leading-6 text-gray-950 dark:text-white">
                        Top Selling SKUs Area Wise
                    </h3>
                    <div class="flex items-center gap-2">
                        <x-filament::input.wrapper>
                            <x-filament::input.select wire:model.live="areaId">
                                <option value="">All areas</option>
                                @foreach ($this->getAreaOptions() as $id => $name)
                                    <option value="{{ $id }}">{{ $name }}</option>
                                @endforeach
                            </x-filament::input.select>
                        </x-filament::input.wrapper>
                        <span class="text-xs text-gray-500 dark:text-gray-400">
                            Last 30 days
                        </span>
                    </div>
                </div>
            </div>

            {{-- ===== Result table ===== --}}
            {{ $this->table }}
        </div>
    </x-filament::section>
</x-filament-widgets::widget>
