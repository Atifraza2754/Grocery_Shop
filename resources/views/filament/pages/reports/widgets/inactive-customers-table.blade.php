<x-filament-widgets::widget class="fi-wi-table">
    <x-filament::section>
        <div class="flex flex-col gap-4">

            {{-- ===== Header: title (left) + filters (right) ===== --}}
            <div class="flex flex-col gap-3 lg:flex-row lg:items-start lg:justify-between">

                {{-- Title --}}
                <div class="flex flex-col gap-1">
                    <h3 class="text-base font-semibold leading-6 text-gray-950 dark:text-white">
                        Inactive customers
                    </h3>
                    <p class="text-xs text-gray-500 dark:text-gray-400">
                        Ordered once, no repeat in <span class="font-medium">{{ $this->getWindowLabel() }}</span>
                    </p>
                </div>

                {{-- Day + custom-date filters --}}
                <div class="flex flex-wrap items-center gap-2">
                    <x-filament::button wire:click="setDays(7)"  size="xs" :color="$this->isDays(7)  ? 'primary' : 'gray'">Last 7 days</x-filament::button>
                    <x-filament::button wire:click="setDays(14)" size="xs" :color="$this->isDays(14) ? 'primary' : 'gray'">Last 14 days</x-filament::button>
                    <x-filament::button wire:click="setDays(30)" size="xs" :color="$this->isDays(30) ? 'primary' : 'gray'">Last 30 days</x-filament::button>

                    <span class="mx-1 hidden text-gray-300 sm:inline dark:text-gray-600">|</span>

                    <x-filament::input.wrapper>
                        <x-filament::input type="date" wire:model.live="fromDate" />
                    </x-filament::input.wrapper>

                    <span class="text-xs text-gray-500">to</span>

                    <x-filament::input.wrapper>
                        <x-filament::input type="date" wire:model.live="toDate" />
                    </x-filament::input.wrapper>

                    @if ($this->fromDate || $this->toDate)
                        <x-filament::button wire:click="clearCustom" size="xs" color="gray" icon="heroicon-o-x-mark">
                            Clear
                        </x-filament::button>
                    @endif
                </div>
            </div>

            {{-- ===== Result table ===== --}}
            {{ $this->table }}
        </div>
    </x-filament::section>
</x-filament-widgets::widget>
