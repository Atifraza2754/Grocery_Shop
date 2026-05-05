@extends('site.layouts.app')

@section('title', 'Track Your Order')

@push('styles')
<style>
    .gs-card { background:#fff; border:1px solid var(--gs-border); border-radius:14px; }

    .order-row {
        display: flex; flex-wrap: wrap; align-items: center; gap: 1rem;
        padding: 1rem; border-bottom: 1px solid var(--gs-border);
    }
    .order-row:last-child { border-bottom: none; }
    .order-no {
        font-family: monospace; font-weight: 700;
        background: #f1f3f5; padding: 4px 10px; border-radius: 6px;
    }
    .status-pill {
        font-size: .8rem; font-weight: 600;
        padding: 3px 11px; border-radius: 999px; display: inline-block;
    }
    .s-pending          { background: #f1f3f5; color: #495057; }
    .s-confirmed        { background: #e0f2fe; color: #0369a1; }
    .s-preparing        { background: #fff8e1; color: #b27300; }
    .s-out_for_delivery { background: #ddd6fe; color: #6d28d9; }
    .s-delivered        { background: #dcfce7; color: #166534; }
    .s-cancelled        { background: #fdecea; color: #c62828; }
</style>
@endpush

@section('content')
<div class="container py-4" style="max-width: 720px;">

    <h2 class="mb-3" style="font-weight:700;">
        <i class="fa-solid fa-truck me-2" style="color: var(--gs-primary);"></i>Track Your Order
    </h2>

    <div class="gs-card p-4 mb-4">
        <form method="POST" action="{{ route('site.track.lookup') }}">
            @csrf
            <label class="form-label fw-semibold">Enter your phone number</label>
            <div class="input-group">
                <input type="tel" name="phone" class="form-control"
                       value="{{ $phone ?? request('phone', '') }}"
                       placeholder="03001234567" required
                       style="border-radius: 10px 0 0 10px;">
                <button class="btn-primary-gs" type="submit" style="border-radius: 0 10px 10px 0;">
                    <i class="fa-solid fa-magnifying-glass me-1"></i> Find orders
                </button>
            </div>
            @error('phone')<div class="small text-danger mt-1">{{ $message }}</div>@enderror
        </form>
    </div>

    @isset($orders)
        <h5 class="mb-3">
            @if ($orders->count())
                Found {{ $orders->count() }} order(s) for {{ $phone }}
            @else
                No orders found for {{ $phone }}
            @endif
        </h5>

        @if ($orders->count())
            <div class="gs-card">
                @foreach ($orders as $o)
                    <div class="order-row">
                        <div>
                            <a href="{{ route('site.order.show', $o->order_no) }}" class="order-no text-decoration-none">
                                {{ $o->order_no }}
                            </a>
                        </div>
                        <div class="flex-grow-1">
                            <div class="text-muted small">{{ $o->created_at->format('M j, Y · H:i') }}</div>
                            @if ($o->area)
                                <div class="text-muted small">{{ $o->area->name }}</div>
                            @endif
                        </div>
                        <div>
                            <span class="status-pill s-{{ $o->status }}">{{ $o->statusLabel() }}</span>
                        </div>
                        <div class="fw-bold" style="color: var(--gs-primary);">
                            Rs {{ number_format((float) $o->total, 0) }}
                        </div>
                        <a href="{{ route('site.order.show', $o->order_no) }}" class="btn btn-sm btn-outline-gs">
                            View
                        </a>
                    </div>
                @endforeach
            </div>
        @endif
    @endisset
</div>
@endsection
