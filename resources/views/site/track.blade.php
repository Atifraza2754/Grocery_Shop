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
    /* Success banner + WhatsApp button */
    .success-banner { background: linear-gradient(135deg, #c8e6c9 0%, #e8f5e9 100%); border-radius: 16px; padding: 2rem; text-align: center; margin-bottom: 1.5rem; }
    .success-icon { width: 70px; height: 70px; background: var(--gs-primary); color: #fff; border-radius: 50%; display: inline-flex; align-items: center; justify-content: center; font-size: 2rem; margin-bottom: 1rem; box-shadow: 0 4px 12px rgba(46,125,50,.3); }
    .btn-whatsapp { background: #25D366; color: #fff; border: none; display: inline-flex; gap: .5rem; align-items: center; padding: 10px 16px; border-radius: 999px; font-weight:700; box-shadow: 0 6px 18px rgba(37,211,102,0.12); text-decoration: none; }

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
        .sticky-actions { display: flex !important; position: fixed; left: 0; right: 0; bottom: 12px; z-index: 1200; justify-content:center; }
        .sticky-actions .bar { width: calc(100% - 32px); max-width: 720px; display:flex; gap: 8px; }
        .sticky-actions .bar a { flex: 1; text-align: center; padding: 8px 10px; }


            /* reduce sizes for mobile to match screenshot 1 */
        .success-icon { width: 54px; height: 54px; font-size: 1.35rem; margin-bottom: .6rem; margin-top: -17px}
        .success-banner h2 { font-size: 16px; line-height: 1.2; }
        .success-banner p, .success-banner .text-muted { font-size: 17px; }
         .success-banner{ margin-bottom: 0.5rem; }
        .success-page{ padding: 0.5rem; margin-top: -30px; }
        .btn-whatsapp { padding: 8px 12px; font-size: .95rem; border-radius: 22px; }
    }
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
        @if ($orders->count())
            @php $first = $orders->first(); @endphp

            @if ($first->status === 'pending')

            <div class="success-banner mb-3">
                <div class="success-icon">
                    <i class="fa-solid fa-check"></i>
                </div>
                <h2 style="font-weight: 800; color: var(--gs-primary-dark); margin: 0;">🎉 شکریہ! آپ کا آرڈر ریکارڈ ہو چکا ہے۔</h2>
                <p class="mb-0 mt-2" style="font-family: monospace; color: var(--gs-primary-dark);">
                    Order number:
                    <strong style="user-select: all;">{{ $first->order_no }}</strong>
                </p>
                <p class="text-muted small mb-0 mt-2">آڈر کی فوری کنفرمیشن اور پراسسنگ کے لیے WhatsApp پر "Send" کر دیں</p>
                <div class="mt-3">
                    <a href="#" id="gsOpenWhatsApp" class="btn-whatsapp">
                        <i class="fa-brands fa-whatsapp"></i>
                        <span>WhatsApp Click to Chat</span>
                    </a>
                </div>
            </div>
            @endif

            {{-- desktop actions removed — single Continue button will be shown after order list --}}

            <h5 class="mb-3">Found {{ $orders->count() }} order(s) for {{ $phone }}</h5>

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

            {{-- Single Continue shopping button after order details (both web & mobile) --}}
            <div class="mt-4 text-center">
                <a href="{{ route('site.home') }}" class="btn-primary-gs text-decoration-none">
                    <i class="fa-solid fa-house me-1"></i> Continue shopping
                </a>
            </div>
        @else
            <h5 class="mb-3">No orders found for {{ $phone }}</h5>
        @endif
    @endisset
</div>

    {{-- Removed mobile sticky two-button bar; single Continue button is shown below order details instead. --}}

@endsection

@push('scripts')
@isset($orders)
    @if ($orders->count())
        <script>
            (function(){
                const waNumber = @json(config('services.whatsapp.admin_phone'));
                if (! waNumber) return;

                const message = {!! json_encode($first->toShareableText()) !!};
                const encoded = encodeURIComponent(message);
                const waMobile = 'https://wa.me/' + waNumber + '?text=' + encoded;
                const waWeb    = 'https://web.whatsapp.com/send?phone=' + waNumber + '&text=' + encoded;
                const isMobile = /Mobi|Android|iPhone|iPad|iPod|Windows Phone/i.test(navigator.userAgent || '');
                const target = isMobile ? waMobile : waWeb;

                const btn = document.getElementById('gsOpenWhatsApp');
                if (btn) {
                    btn.setAttribute('href', target);
                    btn.addEventListener('click', function(e){ e.preventDefault(); window.open(target, '_blank'); });
                }

                // No auto-open on track page — user can tap the WhatsApp button manually
            })();
        </script>
    @endif
@endisset
@endpush
