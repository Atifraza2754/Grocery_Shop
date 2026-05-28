<x-filament-widgets::widget class="fi-wi-table">
    <x-filament::section>
        <div class="flex flex-col gap-4">

            {{-- ===== Header: title + date range ===== --}}
            <div class="flex flex-col gap-3 lg:flex-row lg:items-start lg:justify-between">

                {{-- Title --}}
                <div class="flex flex-col gap-1">
                    <h3 class="text-base font-semibold leading-6 text-gray-950 dark:text-white">
                        Most frequently orders
                    </h3>
                    <p class="text-xs text-gray-500 dark:text-gray-400">
                        {{ $this->getWindowLabel() }} · {{ $this->getTotalOrders() }} orders
                    </p>
                </div>

                {{-- From / To date range --}}
                <div class="flex flex-wrap items-end gap-3">
                    <div class="flex flex-col gap-1">
                        <label class="text-xs font-medium text-gray-500 dark:text-gray-400">From date</label>
                        <x-filament::input.wrapper>
                            <x-filament::input type="date" wire:model.live="fromDate" />
                        </x-filament::input.wrapper>
                    </div>

                    <div class="flex flex-col gap-1">
                        <label class="text-xs font-medium text-gray-500 dark:text-gray-400">To date</label>
                        <x-filament::input.wrapper>
                            <x-filament::input type="date" wire:model.live="toDate" />
                        </x-filament::input.wrapper>
                    </div>
                </div>
            </div>

            {{-- ===== 24-hour breakdown table ===== --}}
            <div class="overflow-x-auto rounded-lg border border-gray-200 dark:border-white/10">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="bg-gray-50 dark:bg-white/5">
                            <th class="px-3 py-2 text-left font-semibold text-gray-950 dark:text-white">
                                Time Frame
                            </th>
                            <th class="px-3 py-2 text-right font-semibold text-gray-950 dark:text-white">
                                Order
                            </th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($this->getPagedBuckets() as $bucket)
                            <tr class="border-t border-gray-100 dark:border-white/5">
                                <td class="px-3 py-2 text-gray-400 dark:text-gray-500">
                                    {{ $bucket['label'] }}
                                </td>
                                <td class="px-3 py-2 text-right font-medium text-gray-950 dark:text-white">
                                    {{ $bucket['count'] }}
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                    <tfoot>
                        <tr class="border-t border-gray-200 bg-gray-50 dark:border-white/10 dark:bg-white/5">
                            <td class="px-3 py-2 text-right font-semibold text-gray-950 dark:text-white">
                                Total
                            </td>
                            <td class="px-3 py-2 text-right font-bold text-gray-950 dark:text-white">
                                {{ $this->getTotalOrders() }}
                            </td>
                        </tr>
                    </tfoot>
                </table>
            </div>

            {{-- ===== Pager (12 slots per page) ===== --}}
            @if ($this->getPageCount() > 1)
                <div class="flex items-center justify-end gap-3">
                    <span class="text-xs text-gray-500 dark:text-gray-400">
                        Page {{ $this->page }} of {{ $this->getPageCount() }}
                    </span>
                    <x-filament::button
                        wire:click="prevPage"
                        size="xs"
                        color="gray"
                        icon="heroicon-o-chevron-left"
                        :disabled="$this->page <= 1"
                    >
                        Prev
                    </x-filament::button>
                    <x-filament::button
                        wire:click="nextPage"
                        size="xs"
                        color="gray"
                        icon="heroicon-o-chevron-right"
                        :disabled="$this->page >= $this->getPageCount()"
                    >
                        Next
                    </x-filament::button>
                </div>
            @endif
        </div>
    </x-filament::section>
</x-filament-widgets::widget>
