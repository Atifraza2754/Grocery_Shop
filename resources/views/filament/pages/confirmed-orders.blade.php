<x-filament-panels::page>

    {{-- Top bar: tabs pinned left, Generate button pinned right.
         The inline margin overrides the tabs component's built-in
         `mx-auto`, and margin-right:auto pushes the button to the edge. --}}
    <div class="flex flex-wrap items-center gap-4">

        <x-filament::tabs style="margin-left: 0; margin-right: auto;">
            <x-filament::tabs.item
                tag="button"
                :active="$activeTab === 'order_products'"
                wire:click="$set('activeTab', 'order_products')"
            >
                Single product list
            </x-filament::tabs.item>

            <x-filament::tabs.item
                tag="button"
                :active="$activeTab === 'product_items'"
                wire:click="$set('activeTab', 'product_items')"
            >
                Multi product list
            </x-filament::tabs.item>
        </x-filament::tabs>

        {{-- Builds the Cash Memo PDF for whichever tab is active. --}}
        <x-filament::button
            tag="button"
            icon="heroicon-o-document-arrow-down"
            wire:click="generate"
            wire:loading.attr="disabled"
            wire:target="generate"
        >
            <span wire:loading.remove wire:target="generate">Print</span>
            <span wire:loading wire:target="generate">Generating…</span>
        </x-filament::button>
    </div>

    {{-- Native Filament table: same theme, borders, pagination & scroll as
         every other admin table. Columns are the categories (auto-extending),
         one row per Confirmed order. --}}
    {{ $this->table }}

</x-filament-panels::page>
