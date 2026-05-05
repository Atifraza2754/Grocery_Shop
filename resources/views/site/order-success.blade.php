@extends('site.layouts.app')

@section('title', 'Order ' . $order->order_no)

@push('styles')
<style>
    .success-banner {
        background: linear-gradient(135deg, #c8e6c9 0%, #e8f5e9 100%);
        border-radius: 16px; padding: 2rem; text-align: center;
        margin-bottom: 1.5rem;
    }
    .success-icon {
        width: 70px; height: 70px;
        background: var(--gs-primary); color: #fff;
        border-radius: 50%;
        display: inline-flex; align-items: center; justify-content: center;
        font-size: 2rem; margin-bottom: 1rem;
        box-shadow: 0 4px 12px rgba(46,125,50,.3);
    }

    .timeline {
        display: flex; justify-content: space-between;
        position: relative; padding: 1rem 0; margin: 1rem 0;
        flex-wrap: wrap; gap: .5rem;
    }
    .timeline-step {
        flex: 1; min-width: 100px; text-align: center;
        position: relative; z-index: 2;
    }
    .timeline-dot {
        width: 32px; height: 32px; border-radius: 50%;
        background: #e9ecef; color: #adb5bd;
        display: inline-flex; align-items: center; justify-content: center;
        margin-bottom: 8px; font-size: .9rem;
    }
    .timeline-step.done .timeline-dot { background: var(--gs-primary); color: #fff; }
    .timeline-step.current .timeline-dot {
        background: var(--gs-primary); color: #fff;
        box-shadow: 0 0 0 4px rgba(46,125,50,.2);
        animation: pulse 1.6s ease-in-out infinite;
    }
    .timeline-step .label { font-size: .8rem; color: var(--gs-muted); }
    .timeline-step.done .label, .timeline-step.current .label { color: var(--gs-text); font-weight: 600; }

    @keyframes pulse {
        0%,100% { box-shadow: 0 0 0 4px rgba(46,125,50,.2); }
        50%     { box-shadow: 0 0 0 8px rgba(46,125,50,.05); }
    }

    .gs-card { background:#fff; border:1px solid var(--gs-border); border-radius:14px; }
    .summary-row { display: flex; justify-content: space-between; padding: .35rem 0; }
    .summary-row.total {
        border-top: 1px dashed var(--gs-border);
        padding-top: .85rem; margin-top: .35rem;
        font-weight: 800; font-size: 1.15rem;
    }
    .item-line {
        display: flex; align-items: center; gap: .75rem;
        padding: .5rem 0; border-bottom: 1px solid var(--gs-border);
        font-size: .92rem;
    }
    .item-line:last-child { border-bottom: none; }
    .item-thumb {
        width: 44px; height: 44px; border-radius: 8px;
        background: #f8f9fa; flex-shrink: 0;
        display: flex; align-items: center; justify-content: center; overflow: hidden;
    }
    .item-thumb img { max-width: 100%; max-height: 100%; object-fit: contain; padding: 4px; }
</style>
@endpush

@php
    $statuses = [
        'pending'          => ['Pending', 'fa-clock'],
        'confirmed'        => ['Confirmed', 'fa-check'],
        'preparing'        => ['Preparing', 'fa-fire'],
        'out_for_delivery' => ['Out for delivery', 'fa-truck'],
        'delivered'        => ['Delivered', 'fa-house'],
    ];
    $cancelled  = $order->status === 'cancelled';
    $orderStage = array_search($order->status, array_keys($statuses), true);
@endphp

@section('content')
<div class="container py-4" style="max-width: 920px;">

    {{-- BANNER --}}
    <div class="success-banner">
        <div class="success-icon">
            @if ($cancelled)
                <i class="fa-solid fa-xmark"></i>
            @else
                <i class="fa-solid fa-check"></i>
            @endif
        </div>
        <h2 style="font-weight: 800; color: var(--gs-primary-dark); margin: 0;">
            @if ($cancelled)
                Order cancelled
            @else
                Thank you! Order placed.
            @endif
        </h2>
        <p class="mb-0 text-muted">
            Order number:
            <strong style="font-family: monospace; color: var(--gs-primary-dark); user-select: all;">
                {{ $order->order_no }}
            </strong>
        </p>
        <p class="text-muted small mb-0 mt-2">
            We'll contact you on <strong>{{ $order->customer_phone }}</strong> shortly to confirm.
        </p>
    </div>

    {{-- TIMELINE --}}
    @if (! $cancelled)
        <div class="gs-card p-3 mb-3">
            <div class="timeline">
                @foreach ($statuses as $key => [$label, $icon])
                    @php
                        $idx     = array_search($key, array_keys($statuses), true);
                        $isDone  = $orderStage !== false && $idx < $orderStage;
                        $isCur   = $order->status === $key;
                    @endphp
                    <div class="timeline-step {{ $isDone ? 'done' : '' }} {{ $isCur ? 'current' : '' }}">
                        <div class="timeline-dot"><i class="fa-solid {{ $icon }}"></i></div>
                        <div class="label">{{ $label }}</div>
                    </div>
                @endforeach
            </div>
        </div>
    @endif

    <div class="row g-3">
        <div class="col-md-7">
            <div class="gs-card p-3">
                <h6 class="fw-bold mb-3">Items ({{ $order->items->count() }})</h6>
                @foreach ($order->items as $item)
                    @php
                        $img = $item->product?->image
                            ? \Illuminate\Support\Facades\Storage::url($item->product->image)
                            : null;
                    @endphp
                    <div class="item-line">
                        <div class="item-thumb">
                            @if ($img)<img src="{{ $img }}" alt="">@else<i class="fa-solid fa-image text-muted"></i>@endif
                        </div>
                        <div class="flex-grow-1">
                            <div class="fw-semibold">{{ $item->name }}</div>
                            <div class="text-muted small">
                                {{ rtrim(rtrim((string) $item->qty, '0'), '.') }} {{ $item->unit }}
                                × Rs {{ number_format((float) $item->price, 0) }}
                            </div>
                        </div>
                        <div class="fw-bold" style="color: var(--gs-primary);">
                            Rs {{ number_format((float) $item->line_total, 0) }}
                        </div>
                    </div>
                @endforeach
            </div>
        </div>

        <div class="col-md-5">
            <div class="gs-card p-3 mb-3">
                <h6 class="fw-bold mb-2">Delivery to</h6>
                <div>{{ $order->customer_name }}</div>
                <div class="text-muted small">{{ $order->customer_phone }}</div>
                <div class="text-muted small mt-1">
                    {{ $order->delivery_address }}
                    @if ($order->area)<br><strong>{{ $order->area->name }}</strong>@endif
                </div>
            </div>

            <div class="gs-card p-3">
                <h6 class="fw-bold mb-3">Bill</h6>
                <div class="summary-row">
                    <span>Subtotal</span>
                    <span>Rs {{ number_format((float) $order->subtotal, 0) }}</span>
                </div>
                @if ((float) $order->discount > 0)
                    <div class="summary-row">
                        <span>Discount @if ($order->coupon_code)({{ $order->coupon_code }})@endif</span>
                        <span class="text-success">- Rs {{ number_format((float) $order->discount, 0) }}</span>
                    </div>
                @endif
                <div class="summary-row">
                    <span>Delivery</span>
                    <span>Rs {{ number_format((float) $order->delivery_charge, 0) }}</span>
                </div>
                <div class="summary-row total">
                    <span>Total</span>
                    <span style="color: var(--gs-primary);">Rs {{ number_format((float) $order->total, 0) }}</span>
                </div>

                <div class="text-muted small mt-2">
                    Payment: <strong>{{ ucfirst(str_replace('_', ' ', $order->payment_method)) }}</strong>
                    · {{ ucfirst($order->payment_status) }}
                </div>
            </div>
        </div>
    </div>

    <div class="d-flex gap-2 mt-4 flex-wrap">
        <a href="{{ route('site.home') }}" class="btn-primary-gs text-decoration-none">
            <i class="fa-solid fa-house me-1"></i> Continue shopping
        </a>
        <a href="{{ route('site.track') }}?phone={{ urlencode($order->customer_phone) }}"
           class="btn btn-outline-gs">
            <i class="fa-solid fa-truck me-1"></i> Track this order
        </a>
    </div>
</div>
@endsection
