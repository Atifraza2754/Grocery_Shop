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

    /* WhatsApp button */
    .btn-whatsapp {
        background: #25D366; color: #fff; border: none; display: inline-flex; gap: .5rem;
        align-items: center; padding: 10px 16px; border-radius: 999px; font-weight:700;
        box-shadow: 0 6px 18px rgba(37,211,102,0.12);
        text-decoration: none;
    }

    /* Ensure only one set of actions shows: desktop vs mobile sticky */
    .desktop-actions { display: none !important; }
    .sticky-actions { display: none !important; }

    /* Desktop: show desktop-actions, hide sticky bar */
    @media (min-width: 576px) {
        .desktop-actions { display: flex !important; gap: .75rem; }
        .sticky-actions { display: none !important; }
    }

    /* Mobile: show sticky bar, hide desktop actions, reduce sizes */
    @media (max-width: 575.98px) {
        .desktop-actions { display: none !important; }
        .sticky-actions { display: flex !important; position: fixed; left: 0; right: 0; bottom: 12px; z-index: 1200; justify-content: center; }
        .sticky-actions .bar { width: calc(100% - 32px); max-width: 920px; display:flex; gap: 8px; }
        .sticky-actions .bar a { flex: 1; text-align: center; padding: 9px 4px; }

        /* reduce sizes for mobile to match screenshot 1 */
        .success-icon { width: 54px; height: 54px; font-size: 1.35rem; margin-bottom: .6rem; margin-top: -17px}
        .success-banner h2 { font-size: 1.05rem; line-height: 1.2; }
        .success-banner p, .success-banner .text-muted { font-size: 1rem; font-weight: 600; }
         .success-banner{ margin-bottom: 0.5rem; }
        .success-page{ padding: 0.5rem; margin-top: -30px; }
        .btn-whatsapp { padding: 8px 12px; font-size: .95rem; border-radius: 22px; }

        .timeline{
            margin:0rem 0;
            padding: 0rem 0;
        }
    }
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
<div class="container py-4 success-page" style="max-width: 920px;">

    {{-- BANNER: Urdu style like the screenshot --}}
    @if ($order->status === 'pending')
    <div class="success-banner">
        <div class="success-icon">
            @if ($cancelled)
                <i class="fa-solid fa-xmark"></i>
            @else
                <i class="fa-solid fa-check"></i>
            @endif
        </div>
        @if ($cancelled)
            <h2 style="font-weight: 800; color: var(--gs-primary-dark); margin: 0;">Order cancelled</h2>
        @else
            <h2 style="font-weight: 700; color: var(--gs-primary-dark); margin: 0;">🎉 شکریہ! آپ کا آرڈر ریکارڈ ہو چکا ہے۔</h2>
            <p class="mb-0 mt-2" style="font-family: monospace; color: var(--gs-primary-dark);">
                Order number:
                <strong style="user-select: all;">{{ $order->order_no }}</strong>
            </p>
            <p class="small mb-0 mt-2">آڈر کی فوری کنفرمیشن اور پراسسنگ کے لیے WhatsApp پر "Send" کر دیں</p>
            <div class="mt-3">
                <a href="#" id="gsOpenWhatsApp" class="btn-whatsapp">
                    <i class="fa-brands fa-whatsapp"></i>
                    <span>WhatsApp Click to Chat</span>
                </a>
            </div>
        @endif
    </div>
    @endif

    {{-- TIMELINE --}}
    @if (! $cancelled)
        <div class="gs-card p-3 mb-3 timeline-card">
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

    <div class="d-flex gap-2 mt-4 flex-wrap desktop-actions">
        <a href="{{ route('site.home') }}" class="btn-primary-gs text-decoration-none">
            <i class="fa-solid fa-house me-1"></i> Continue shopping
        </a>
        <a href="{{ route('site.track') }}?phone={{ urlencode($order->customer_phone) }}"
           class="btn btn-outline-gs">
            <i class="fa-solid fa-truck me-1"></i> Track this order
        </a>
    </div>

    {{-- Sticky bottom actions (Continue / Track) --}}
    <div class="sticky-actions" aria-hidden="false">
        <div class="bar">
            <a href="{{ route('site.home') }}" class="btn-primary-gs text-decoration-none">
                <i class="fa-solid fa-house me-1"></i> Continue shopping
            </a>
            <a href="{{ route('site.track') }}?phone={{ urlencode($order->customer_phone) }}"
               class="btn btn-outline-gs bg-white">
                <i class="fa-solid fa-truck me-1"></i> Track this order
            </a>
        </div>
    </div>


@push('scripts')
<script>
    (function(){
        const waNumber = @json(config('services.whatsapp.admin_phone'));
        if (! waNumber) return; // no admin phone configured — do nothing

        const message = {!! json_encode($order->toShareableText()) !!};
        const encoded = encodeURIComponent(message);

        const waMobile = 'https://wa.me/' + waNumber + '?text=' + encoded;
        const waWeb    = 'https://web.whatsapp.com/send?phone=' + waNumber + '&text=' + encoded;

        const isMobile = /Mobi|Android|iPhone|iPad|iPod|Windows Phone/i.test(navigator.userAgent || '');
        const target = isMobile ? waMobile : waWeb;

        // Click handler for visible WhatsApp button
        const btn = document.getElementById('gsOpenWhatsApp');
        if (btn) {
            btn.setAttribute('href', target);
            btn.addEventListener('click', function(e){
                e.preventDefault();
                window.open(target, '_blank');
            });
        }

        // Auto-open after configured delay (default 5s) — only when redirected here after placing order
        @if(session('open_whatsapp'))
            const delaySec = parseInt(@json(config('services.whatsapp.send_delay_sec', 5)), 10) || 5;
            setTimeout(() => { try { window.open(target, '_blank'); } catch (e) { /* ignore */ } }, delaySec * 1000);
        @endif
    })();
</script>
@endpush

@endsection
