@php
    /** Format a decimal qty without trailing zeros (1.000 -> "1", 1.500 -> "1.5"). */
    $fmtQty = fn ($qty) => rtrim(rtrim(number_format((float) $qty, 3, '.', ''), '0'), '.');
@endphp

<div class="flex flex-col gap-4">
    @forelse ($customer->orders as $order)
        <div class="rounded-lg border border-gray-200 p-3 dark:border-white/10">
            <div class="mb-2 flex items-center justify-between gap-2">
                <span class="font-semibold text-gray-950 dark:text-white">
                    Order #{{ $order->order_no }}
                </span>
                <span class="text-xs text-gray-500 dark:text-gray-400">
                    {{ $order->created_at?->format('M j, Y · g:i A') }}
                </span>
            </div>

            @if ($order->items->isEmpty())
                <p class="text-sm text-gray-500 dark:text-gray-400">No products on this order.</p>
            @else
                <ul class="list-disc space-y-1 pl-5 text-sm text-gray-700 dark:text-gray-300">
                    @foreach ($order->items as $item)
                        <li>
                            <span class="font-medium text-gray-950 dark:text-white">{{ $item->name }}</span>
                            <span class="text-gray-500 dark:text-gray-400">
                                — {{ $fmtQty($item->qty) }} {{ $item->unit }}
                            </span>
                        </li>
                    @endforeach
                </ul>
            @endif
        </div>
    @empty
        <p class="text-sm text-gray-500 dark:text-gray-400">No orders found for this customer.</p>
    @endforelse
</div>
