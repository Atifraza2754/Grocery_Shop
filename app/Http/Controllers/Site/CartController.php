<?php

namespace App\Http\Controllers\Site;

use App\Http\Controllers\Controller;
use App\Services\CartService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CartController extends Controller
{
    public function __construct(protected CartService $cart) {}

    public function index()
    {
        $items   = $this->cart->items();
        $totals  = $this->cart->totals(null);

        return view('site.cart', compact('items', 'totals'));
    }

    public function add(Request $request): JsonResponse
    {
        $data = $request->validate([
            'product_id' => 'required|integer|exists:products,id',
            'qty'        => 'nullable|numeric|min:0.001|max:9999',
        ]);

        $result = $this->cart->add(
            (int)   $data['product_id'],
            (float) ($data['qty'] ?? 1)
        );

        return response()->json($result + ['count' => $this->cart->totalQuantity()]);
    }

    public function update(Request $request): JsonResponse
    {
        $data = $request->validate([
            'product_id' => 'required|integer',
            'qty'        => 'required|numeric|min:0|max:9999',
        ]);

        $this->cart->update((int) $data['product_id'], (float) $data['qty']);
        return response()->json([
            'ok'     => true,
            'count'  => $this->cart->totalQuantity(),
            'totals' => $this->cart->totals(null),
        ]);
    }

    public function remove(Request $request): JsonResponse
    {
        $data = $request->validate(['product_id' => 'required|integer']);
        $this->cart->remove((int) $data['product_id']);
        return response()->json([
            'ok'     => true,
            'count'  => $this->cart->totalQuantity(),
            'totals' => $this->cart->totals(null),
        ]);
    }

    public function coupon(Request $request): JsonResponse
    {
        $data = $request->validate(['code' => 'nullable|string|max:32']);
        $result = $this->cart->applyCoupon($data['code'] ?? null);
        return response()->json($result + ['totals' => $this->cart->totals(null)]);
    }

    public function count(): JsonResponse
    {
        return response()->json(['count' => $this->cart->totalQuantity()]);
    }
}
