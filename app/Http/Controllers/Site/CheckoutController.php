<?php

namespace App\Http\Controllers\Site;

use App\Http\Controllers\Controller;
use App\Jobs\SendOrderWhatsApp;
use App\Models\Ambassador;
use App\Models\Area;
use App\Models\Customer;
use App\Models\Order;
use App\Services\CartService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class CheckoutController extends Controller
{
    public function __construct(protected CartService $cart) {}

    public function show(Request $request)
    {
        if ($this->cart->isEmpty()) {
            return redirect()->route('site.cart')
                ->with('error', 'Your cart is empty.');
        }

        $areas = Area::where('is_active', true)
            ->orderBy('sort_order')->orderBy('name')
            ->get();

        // Pre-fill from session if user comes back
        $prefill = session('checkout_prefill', []);

        $items         = $this->cart->items();
        $groceryItems  = $this->cart->groceryItems();
        $totals        = $this->cart->totals(null);

        return view('site.checkout', compact('areas', 'items', 'groceryItems', 'totals', 'prefill'));
    }

    public function place(Request $request)
    {
        if ($this->cart->isEmpty()) {
            return redirect()->route('site.cart')->with('error', 'Your cart is empty.');
        }

        $data = $request->validate([
            'name'             => 'required|string|max:120',
            'phone'            => 'required|string|max:20',
            'address'          => 'required|string|max:500',
            'area_id'          => 'required|integer|exists:areas,id',
            'lat'              => 'nullable|numeric',
            'lng'              => 'nullable|numeric',
            'customer_note'    => 'nullable|string|max:500',
            'payment_method'   => 'nullable|in:cod,cash,transfer,other',
        ]);

        // Remember the prefill for next visit
        session(['checkout_prefill' => $data]);

        $items         = $this->cart->items();
        $groceryItems  = $this->cart->groceryItems();
        $coupon        = $this->cart->coupon();
        $totals        = $this->cart->totals($data['area_id']);

        try {
            $order = DB::transaction(function () use ($data, $items, $groceryItems, $coupon) {
                // Find or create customer by phone
                $customer = Customer::findOrCreateByPhone($data['phone'], [
                    'name'    => $data['name'],
                    'address' => $data['address'],
                    'area_id' => $data['area_id'],
                    'lat'     => $data['lat']    ?? null,
                    'lng'     => $data['lng']    ?? null,
                ]);

                // Update existing customer with latest address/area if missing
                $customer->fill([
                    'name'    => $data['name'],
                    'address' => $data['address'],
                    'area_id' => $data['area_id'],
                    'lat'     => $data['lat'] ?? $customer->lat,
                    'lng'     => $data['lng'] ?? $customer->lng,
                ])->save();

                // Auto-suggest ambassador for this area
                $ambassadorId = Ambassador::where('is_active', true)
                    ->where('area_id', $data['area_id'])
                    ->orderBy('id')
                    ->value('id');

                // Create the order
                $order = Order::create([
                    'customer_id'      => $customer->id,
                    'customer_name'    => $data['name'],
                    'customer_phone'   => $data['phone'],
                    'area_id'          => $data['area_id'],
                    'ambassador_id'    => $ambassadorId,
                    'delivery_address' => $data['address'],
                    'lat'              => $data['lat'] ?? null,
                    'lng'              => $data['lng'] ?? null,
                    'coupon_id'        => $coupon?->id,
                    'coupon_code'      => $coupon?->code,
                    'status'           => Order::STATUS_PENDING,
                    'payment_method'   => $data['payment_method'] ?? 'cod',
                    'payment_status'   => 'pending',
                    'customer_note'    => $data['customer_note'] ?? null,
                ]);

                // Add regular line items (snapshot)
                foreach ($items as $row) {
                    $order->items()->create([
                        'product_id'         => $row->product->id,
                        'is_grocery_request' => false,
                        'sku'                => $row->product->sku,
                        'name'               => $row->product->name,
                        'unit'               => $row->product->unit,
                        'price'              => $row->price,
                        'qty'                => $row->qty,
                    ]);
                }

                // Add grocery items — flagged, no price, admin will set later
                foreach ($groceryItems as $g) {
                    $order->items()->create([
                        'product_id'         => null,
                        'is_grocery_request' => true,
                        'sku'                => null,
                        'name'               => $g['name'],
                        'unit'               => $g['unit'] ?? 'piece',
                        'price'              => 0,
                        'qty'                => $g['qty'] ?? 1,
                    ]);
                }

                // Recalculate totals (also writes delivery_charge from the area)
                $order->refresh()->load(['items', 'coupon', 'area']);
                $order->recalculateTotals();

                return $order;
            });
        } catch (\Throwable $e) {
            return back()->withInput()->with('error', 'Could not place order: ' . $e->getMessage());
        }

        // Clean up
        $this->cart->clear();
        session()->forget('checkout_prefill');

        // Fire WhatsApp notifications (admin + customer) with the configured delay.
        // Falls back to logging if creds not set, never blocks order placement.
        $delay = (int) config('services.whatsapp.send_delay_sec', 5);
        try {
            SendOrderWhatsApp::dispatch($order->id, 'admin')
                ->delay(now()->addSeconds($delay));
            SendOrderWhatsApp::dispatch($order->id, 'customer')
                ->delay(now()->addSeconds($delay));
            // Trigger client-side one-time WhatsApp auto-open on the next page load
            session()->flash('open_whatsapp', true);
        } catch (\Throwable $e) {
            \Log::warning('Could not dispatch WhatsApp jobs: ' . $e->getMessage());
        }

        return redirect()->route('site.order.show', ['orderNo' => $order->order_no]);
    }
}
