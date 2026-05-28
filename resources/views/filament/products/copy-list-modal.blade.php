@php
    $today = now()->format('n/j/Y');

    $lines = [];
    $lines[] = '🛒 *Product List* — ' . $today;
    $lines[] = '';

    foreach ($categories as $category) {
        // Skip categories with no active products
        if ($category->products->isEmpty()) {
            continue;
        }

        // Category name + current date on the same row
        $lines[] = '🏷️ *' . $category->name . '* — ' . $today;

        foreach ($category->products as $product) {
            $unit  = $product->unit ? ' / ' . $product->unit : '';
            $price = 'Rs ' . number_format((float) $product->price, 0);
            $lines[] = '• ' . $product->name . ' — ' . $price . $unit;
        }

        $lines[] = '';
    }

    $text = trim(implode("\n", $lines));
@endphp

<div x-data="{
        copied: false,
        copy() {
            navigator.clipboard.writeText($refs.txt.value);
            this.copied = true;
            setTimeout(() => this.copied = false, 1500);
        },
     }"
     class="space-y-3">

    <textarea x-ref="txt" readonly rows="16"
        class="w-full font-mono text-sm rounded-lg border border-gray-300 dark:border-gray-700 bg-gray-50 dark:bg-gray-900 p-3 focus:outline-none focus:ring-2 focus:ring-emerald-500"
    >{{ $text }}</textarea>

    <div class="flex items-center gap-3">
        <button type="button" @click="copy()"
            class="inline-flex items-center gap-2 rounded-lg bg-emerald-600 hover:bg-emerald-700 px-4 py-2 text-sm font-semibold text-white shadow-sm focus:outline-none focus:ring-2 focus:ring-emerald-500">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor">
                <path d="M7 3a2 2 0 00-2 2v10a2 2 0 002 2h6a2 2 0 002-2V5a2 2 0 00-2-2H7zm0 2h6v10H7V5z"/>
                <path d="M3 7a2 2 0 012-2v10h6a2 2 0 01-2 2H5a2 2 0 01-2-2V7z"/>
            </svg>
            <span x-text="copied ? 'Copied!' : 'Copy to clipboard'"></span>
        </button>
        <span class="text-xs text-gray-500">Paste into WhatsApp, notes, or anywhere.</span>
    </div>
</div>
