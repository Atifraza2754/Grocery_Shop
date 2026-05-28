<x-filament-widgets::widget class="fi-wi-table">
    <x-filament::section>
        <div class="flex flex-col gap-4">

            {{-- ===== Header: title + tabs ===== --}}
            <div class="flex flex-col gap-2">
                <h3 class="text-base font-semibold leading-6 text-gray-950 dark:text-white">
                    Sale Report By Daily / Monthly
                </h3>
                <div class="flex gap-2">
                    <x-filament::button
                        wire:click="setMode('daily')"
                        size="sm"
                        :color="$this->mode === 'daily' ? 'primary' : 'gray'"
                        icon="heroicon-o-calendar"
                    >
                        Daily
                    </x-filament::button>

                    <x-filament::button
                        wire:click="setMode('monthly')"
                        size="sm"
                        :color="$this->mode === 'monthly' ? 'primary' : 'gray'"
                        icon="heroicon-o-calendar-days"
                    >
                        Monthly
                    </x-filament::button>
                </div>
            </div>

            {{-- ===== Result table ===== --}}
            {{ $this->table }}
        </div>
    </x-filament::section>
</x-filament-widgets::widget>
