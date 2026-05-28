@php
    /** @var \App\Models\Order $order */
    $mapsUrl = $order->mapsUrl();
@endphp

<div class="space-y-4 text-sm" x-data="{ copy(t){ navigator.clipboard.writeText(t); } }">

    {{-- Top summary --}}
    <div class="grid grid-cols-2 gap-3">
        @if ($order->manual_order_id)
            <div class="col-span-2">
                <div class="text-xs text-gray-500 uppercase">Manual ID</div>
                <div class="font-mono font-semibold">
                    {{ $order->manual_order_id }}
                    <button type="button"
                            class="ml-1 text-emerald-600 hover:text-emerald-800"
                            @click="copy(@js($order->manual_order_id))">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-3 w-3 inline" viewBox="0 0 20 20" fill="currentColor">
                            <path d="M7 3a2 2 0 00-2 2v10a2 2 0 002 2h6a2 2 0 002-2V5a2 2 0 00-2-2H7zm0 2h6v10H7V5z"/>
                        </svg>
                    </button>
                </div>
            </div>
        @endif

        <div>
            <div class="text-xs text-gray-500 uppercase">Customer</div>
            <div class="font-semibold">{{ $order->customer_name }}</div>
            <div class="font-mono">
                {{ $order->customer_phone }}
                <button type="button"
                        class="ml-1 text-emerald-600 hover:text-emerald-800"
                        @click="copy(@js($order->customer_phone))">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-3 w-3 inline" viewBox="0 0 20 20" fill="currentColor">
                        <path d="M7 3a2 2 0 00-2 2v10a2 2 0 002 2h6a2 2 0 002-2V5a2 2 0 00-2-2H7zm0 2h6v10H7V5z"/>
                    </svg>
                </button>
            </div>
        </div>

        <div>
            <div class="text-xs text-gray-500 uppercase">Area</div>
            <div class="font-semibold">{{ $order->area?->name ?? '—' }}</div>
            @if ($order->ambassador)
                <div class="text-xs text-gray-500">
                    Ambassador: <span class="font-medium">{{ $order->ambassador->name }}</span>
                </div>
            @endif
        </div>

        <div class="col-span-2">
            <div class="text-xs text-gray-500 uppercase">Address</div>
            <div>{{ $order->delivery_address ?? '—' }}</div>
            @if ($mapsUrl)
                <div class="mt-1">
                    <a href="{{ $mapsUrl }}" target="_blank" rel="noopener"
                       class="inline-flex items-center gap-1 text-emerald-700 hover:text-emerald-900 underline">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor">
                            <path fill-rule="evenodd" d="M5.05 4.05a7 7 0 119.9 9.9L10 18.9l-4.95-4.95a7 7 0 010-9.9zM10 11a2 2 0 100-4 2 2 0 000 4z" clip-rule="evenodd"/>
                        </svg>
                        Open in Google Maps
                    </a>
                    <button type="button"
                            class="ml-2 text-gray-500 hover:text-gray-800 text-xs"
                            @click="copy(@js($mapsUrl))">
                        Copy link
                    </button>
                </div>
            @endif
        </div>

        <div>
            <div class="text-xs text-gray-500 uppercase">Status</div>
            <div>
                <span class="inline-block px-2 py-0.5 rounded-full text-xs font-semibold
                    @switch($order->status)
                        @case('pending')          bg-gray-100 text-gray-700 @break
                        @case('confirmed')        bg-sky-100 text-sky-700 @break
                        @case('preparing')        bg-amber-100 text-amber-700 @break
                        @case('out_for_delivery') bg-violet-100 text-violet-700 @break
                        @case('delivered')        bg-emerald-100 text-emerald-700 @break
                        @case('cancelled')        bg-red-100 text-red-700 @break
                    @endswitch">
                    {{ $order->statusLabel() }}
                </span>
                @if ($order->needsPricing())
                    <span class="inline-block px-2 py-0.5 rounded-full text-xs font-semibold bg-amber-100 text-amber-800 ml-1">
                        Needs pricing
                    </span>
                @endif
            </div>
        </div>

        <div>
            <div class="text-xs text-gray-500 uppercase">Total bill</div>
            <div class="text-lg font-bold text-emerald-700">
                Rs {{ number_format((float) $order->total, 2) }}
            </div>
        </div>
    </div>

    <hr>

    {{-- Items --}}
    <div>
        <div class="font-semibold mb-2">Items ({{ $order->items->count() }})</div>
        <div class="border rounded-lg overflow-hidden">
            <table class="w-full text-sm">
                <thead class="bg-gray-50">
                    <tr class="text-left">
                        <th class="px-3 py-2">Item</th>
                        <th class="px-3 py-2 text-right">Qty</th>
                        <th class="px-3 py-2 text-right">Price</th>
                        <th class="px-3 py-2 text-right">Total</th>
                    </tr>
                </thead>
                <tbody>
                @foreach ($order->items as $it)
                    <tr class="border-t {{ $it->isGroceryRequest() ? 'bg-amber-50' : '' }}">
                        <td class="px-3 py-2">
                            <div class="font-medium">{{ $it->name }}</div>
                            @if ($it->sku)
                                <div class="text-xs text-gray-500 font-mono">{{ $it->sku }}</div>
                            @endif
                            @if ($it->isGroceryRequest())
                                <span class="inline-block text-xs px-1.5 py-0.5 rounded bg-amber-200 text-amber-900 mt-1">Custom</span>
                            @endif
                        </td>
                        <td class="px-3 py-2 text-right">
                            {{ rtrim(rtrim((string) $it->qty, '0'), '.') }} {{ $it->unit }}
                        </td>
                        <td class="px-3 py-2 text-right">
                            @if ($it->needsPricing())
                                <span class="text-amber-600">— set price —</span>
                            @else
                                Rs {{ number_format((float) $it->price, 2) }}
                            @endif
                        </td>
                        <td class="px-3 py-2 text-right font-semibold">
                            Rs {{ number_format((float) $it->line_total, 2) }}
                        </td>
                    </tr>
                @endforeach
                </tbody>
            </table>
        </div>
    </div>

    {{-- Totals --}}
    <div class="grid grid-cols-2 gap-2">
        <div class="text-gray-500">Subtotal</div>
        <div class="text-right">Rs {{ number_format((float) $order->subtotal, 2) }}</div>

        @if ((float) $order->discount > 0)
            <div class="text-gray-500">Discount @if($order->coupon_code)({{ $order->coupon_code }})@endif</div>
            <div class="text-right text-emerald-600">- Rs {{ number_format((float) $order->discount, 2) }}</div>
        @endif

        <div class="text-gray-500">Delivery</div>
        <div class="text-right">Rs {{ number_format((float) $order->delivery_charge, 2) }}</div>

        <div class="font-bold text-base border-t pt-2 mt-1">Total</div>
        <div class="font-bold text-base text-right text-emerald-700 border-t pt-2 mt-1">
            Rs {{ number_format((float) $order->total, 2) }}
        </div>
    </div>

    @if ($order->customer_note)
        <div class="bg-blue-50 border-l-4 border-blue-400 p-2 rounded text-sm">
            <div class="font-semibold text-blue-700 text-xs">Customer note</div>
            <div>{{ $order->customer_note }}</div>
        </div>
    @endif
</div>
