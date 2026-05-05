<?php

namespace App\Services;

use App\Models\Coupon;
use App\Models\Product;
use Illuminate\Support\Facades\Session;

/**
 * Session-based cart for the customer-facing site.
 *
 * Storage shape:
 *   session('gs_cart') = [
 *      'items'       => [ productId => ['product_id' => 1, 'qty' => 2] ],
 *      'coupon_id'   => null|int,
 *      'coupon_code' => null|string,
 *   ]
 */
class CartService
{
    protected const KEY = 'gs_cart';

    /* ---------- Raw state ---------- */

    public function state(): array
    {
        return Session::get(self::KEY, [
            'items'       => [],
            'coupon_id'   => null,
            'coupon_code' => null,
        ]);
    }

    protected function save(array $state): void
    {
        Session::put(self::KEY, $state);
    }

    public function clear(): void
    {
        Session::forget(self::KEY);
    }

    /* ---------- Mutations ---------- */

    public function add(int $productId, float $qty = 1): array
    {
        $product = Product::active()->find($productId);
        if (! $product) {
            return ['ok' => false, 'message' => 'Product not available.'];
        }

        $state = $this->state();
        $existing = $state['items'][$productId] ?? null;

        $newQty = max(1, (float) ($existing['qty'] ?? 0) + $qty);
        $state['items'][$productId] = [
            'product_id' => $productId,
            'qty'        => $newQty,
        ];

        $this->save($state);

        return [
            'ok'      => true,
            'message' => $product->name . ' added to cart',
            'count'   => $this->totalQuantity(),
        ];
    }

    public function update(int $productId, float $qty): array
    {
        $state = $this->state();

        if ($qty <= 0) {
            unset($state['items'][$productId]);
        } elseif (isset($state['items'][$productId])) {
            $state['items'][$productId]['qty'] = $qty;
        }

        $this->save($state);
        return ['ok' => true, 'count' => $this->totalQuantity()];
    }

    public function remove(int $productId): array
    {
        $state = $this->state();
        unset($state['items'][$productId]);
        $this->save($state);
        return ['ok' => true, 'count' => $this->totalQuantity()];
    }

    /* ---------- Coupon ---------- */

    public function applyCoupon(?string $code): array
    {
        $state = $this->state();

        if (! $code) {
            $state['coupon_id'] = null;
            $state['coupon_code'] = null;
            $this->save($state);
            return ['ok' => true, 'message' => 'Coupon removed', 'discount' => 0];
        }

        $code   = strtoupper(trim($code));
        $coupon = Coupon::where('code', $code)->first();

        if (! $coupon) {
            return ['ok' => false, 'message' => 'Coupon code not found.'];
        }

        $check = $coupon->validateAgainst($this->subtotal());
        if (! $check['ok']) {
            return ['ok' => false, 'message' => $check['message']];
        }

        $state['coupon_id']   = $coupon->id;
        $state['coupon_code'] = $coupon->code;
        $this->save($state);

        return [
            'ok'       => true,
            'message'  => "Coupon {$coupon->code} applied",
            'discount' => $check['discount'],
        ];
    }

    /* ---------- Reads ---------- */

    /**
     * Hydrate cart items with current product data.
     * Returns array of objects: { product, qty, line_total }
     * Drops items whose product was deleted/deactivated.
     */
    public function items(): array
    {
        $state = $this->state();
        if (empty($state['items'])) return [];

        $ids = array_keys($state['items']);
        $products = Product::with('category')
            ->whereIn('id', $ids)
            ->where('is_active', true)
            ->get()
            ->keyBy('id');

        $rows = [];
        foreach ($state['items'] as $pid => $row) {
            $p = $products->get($pid);
            if (! $p) continue;
            $qty   = (float) $row['qty'];
            $price = (float) $p->price;
            $rows[] = (object) [
                'product'    => $p,
                'qty'        => $qty,
                'price'      => $price,
                'line_total' => round($price * $qty, 2),
            ];
        }
        return $rows;
    }

    public function totalQuantity(): int
    {
        $sum = 0;
        foreach ($this->state()['items'] as $row) {
            $sum += (int) ceil((float) $row['qty']);
        }
        return $sum;
    }

    public function isEmpty(): bool
    {
        return count($this->items()) === 0;
    }

    public function subtotal(): float
    {
        $sum = 0.0;
        foreach ($this->items() as $row) $sum += $row->line_total;
        return round($sum, 2);
    }

    public function coupon(): ?Coupon
    {
        $state = $this->state();
        return $state['coupon_id'] ? Coupon::find($state['coupon_id']) : null;
    }

    public function discount(): float
    {
        $c = $this->coupon();
        if (! $c) return 0.0;
        $check = $c->validateAgainst($this->subtotal());
        return $check['ok'] ? (float) $check['discount'] : 0.0;
    }

    public function deliveryCharge(?int $areaId): float
    {
        if (! $areaId) return 0.0;
        $area = \App\Models\Area::find($areaId);
        return $area ? (float) $area->delivery_charge : 0.0;
    }

    public function totals(?int $areaId = null): array
    {
        $subtotal = $this->subtotal();
        $discount = $this->discount();
        $delivery = $this->deliveryCharge($areaId);
        $total    = max(0, $subtotal - $discount + $delivery);

        return [
            'subtotal'        => $subtotal,
            'discount'        => $discount,
            'delivery_charge' => $delivery,
            'total'           => round($total, 2),
            'coupon_code'     => $this->state()['coupon_code'] ?? null,
        ];
    }
}
