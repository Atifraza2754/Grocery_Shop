<?php

namespace App\Http\Controllers\Site;

use App\Http\Controllers\Controller;
use App\Models\Order;
use Illuminate\Http\Request;

class OrderController extends Controller
{
    public function show(string $orderNo)
    {
        $order = Order::where('order_no', $orderNo)
            ->with(['items.product', 'area', 'coupon'])
            ->firstOrFail();

        return view('site.order-success', compact('order'));
    }

    public function trackForm()
    {
        return view('site.track');
    }

    public function trackLookup(Request $request)
    {
        $data = $request->validate(['phone' => 'required|string|max:20']);

        $orders = Order::where('customer_phone', $data['phone'])
            ->with(['area'])
            ->orderByDesc('id')
            ->limit(20)
            ->get();

        return view('site.track', [
            'phone'  => $data['phone'],
            'orders' => $orders,
        ]);
    }
}
