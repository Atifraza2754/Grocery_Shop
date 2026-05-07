<?php

namespace App\Jobs;

use App\Models\Order;
use App\Services\WhatsAppService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class SendOrderWhatsApp implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /** Backoff in seconds — first try after 5s, then retry with exp backoff. */
    public array $backoff = [10, 30];

    public int $tries = 3;

    public function __construct(
        public int $orderId,
        public string $audience, // 'admin' | 'customer'
    ) {}

    public function handle(WhatsAppService $whatsapp): void
    {
        $order = Order::with(['items', 'area'])->find($this->orderId);
        if (! $order) return;

        $to = match ($this->audience) {
            'customer' => $order->customer_phone,
            'admin'    => config('services.whatsapp.admin_phone'),
            default    => null,
        };

        if (! $to) return;

        $message = $this->buildMessage($order);
        $whatsapp->send($to, $message);
    }

    protected function buildMessage(Order $order): string
    {
        if ($this->audience === 'admin') {
            return $this->adminMessage($order);
        }
        return $this->customerMessage($order);
    }

    protected function customerMessage(Order $order): string
    {
        $brand = config('app.name', 'Grocery Shop');
        $lines = [];
        $lines[] = "🛒 *{$brand}* — Order received";
        $lines[] = "";
        $lines[] = "Hi {$order->customer_name}, thanks for your order!";
        $lines[] = "Your order *{$order->order_no}* has been received.";
        $lines[] = "";
        $lines[] = "📦 *Items*";
        foreach ($order->items as $it) {
            $qty = rtrim(rtrim((string) $it->qty, '0'), '.');
            $lines[] = "• {$it->name} — {$qty} {$it->unit}";
        }
        $lines[] = "";
        if ($order->needsPricing()) {
            $lines[] = "Some items will be priced manually — we'll confirm via WhatsApp.";
        } else {
            $lines[] = "💰 Total: Rs " . number_format((float) $order->total, 2);
        }
        $lines[] = "";
        $lines[] = "We'll be in touch shortly to confirm.";
        return implode("\n", $lines);
    }

    protected function adminMessage(Order $order): string
    {
        $lines = [];
        $lines[] = "📥 *New Order: {$order->order_no}*";
        $lines[] = "Customer: {$order->customer_name}";
        $lines[] = "Phone: {$order->customer_phone}";
        if ($order->area) $lines[] = "Area: {$order->area->name}";
        if ($order->delivery_address) $lines[] = "Address: {$order->delivery_address}";
        if ($mapsUrl = $order->mapsUrl()) {
            $lines[] = "Location: {$mapsUrl}";
        }
        $lines[] = "";
        $lines[] = "*Items*";
        foreach ($order->items as $it) {
            $qty = rtrim(rtrim((string) $it->qty, '0'), '.');
            $custom = $it->isGroceryRequest() ? ' [CUSTOM]' : '';
            $lines[] = "• {$it->name} — {$qty} {$it->unit}{$custom}";
        }
        $lines[] = "";
        if ($order->needsPricing()) {
            $lines[] = "⚠️ *Has unpriced grocery items — set prices in admin.*";
        } else {
            $lines[] = "💰 Total: Rs " . number_format((float) $order->total, 2);
        }
        return implode("\n", $lines);
    }
}
