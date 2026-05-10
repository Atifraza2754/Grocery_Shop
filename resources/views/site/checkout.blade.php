@extends('site.layouts.app')

@section('title', 'Checkout')

@push('styles')
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css">
<style>
    .gs-card { background:#fff; border:1px solid var(--gs-border); border-radius:14px; }
    #pickerMap { height: 280px; border-radius: 10px; margin-top: .5rem; border: 1px solid var(--gs-border); }
    #pickerMap.hidden { display: none; }
    .form-label { font-weight: 600; color: var(--gs-text); margin-bottom: 4px; font-size: .92rem; }
    .form-control, .form-select {
        border-radius: 10px; border: 1.5px solid #d0d7de; padding: 10px 12px;
    }
    .form-control:focus, .form-select:focus {
        border-color: var(--gs-primary);
        box-shadow: 0 0 0 0.2rem rgba(46,125,50,.18);
    }

    .summary-row { display: flex; justify-content: space-between; padding: .35rem 0; }
    .summary-row.total {
        border-top: 1px dashed var(--gs-border);
        padding-top: .85rem; margin-top: .35rem;
        font-weight: 800; font-size: 1.2rem;
    }

    .item-line {
        display: flex; align-items: center; gap: .75rem;
        padding: .5rem 0; border-bottom: 1px solid var(--gs-border);
        font-size: .9rem;
    }
    .item-line:last-child { border-bottom: none; }
    .item-thumb {
        width: 44px; height: 44px; border-radius: 8px;
        background: #f8f9fa; flex-shrink: 0;
        display: flex; align-items: center; justify-content: center; overflow: hidden;
    }
    .item-thumb img { max-width: 100%; max-height: 100%; object-fit: contain; padding: 4px; }
    .pay-option {
        border: 1.5px solid var(--gs-border); border-radius: 10px;
        padding: 10px 14px; cursor: pointer;
        display: flex; align-items: center; gap: 10px;
        transition: all .2s;
    }
    .pay-option:has(input:checked) {
        border-color: var(--gs-primary); background: var(--gs-primary-soft);
    }
    .pay-option input { margin: 0; }
</style>
@endpush

@section('content')
<div class="container py-4">

    <div class="d-flex align-items-center justify-content-between mb-3 flex-wrap gap-2">
        <h2 class="m-0" style="font-weight: 700;">
            <i class="fa-solid fa-credit-card me-2" style="color: var(--gs-primary);"></i>Checkout
        </h2>
        <a href="{{ route('site.cart') }}" class="btn btn-outline-gs">
            <i class="fa-solid fa-arrow-left me-1"></i> Back to cart
        </a>
    </div>

    @if (session('error'))
        <div class="alert alert-danger">{{ session('error') }}</div>
    @endif

    <form method="POST" action="{{ route('site.checkout.place') }}" id="checkoutForm">
        @csrf
        <div class="row g-4">

            {{-- ============ FORM ============ --}}
            <div class="col-lg-7">
                <div class="gs-card p-4 mb-3">
                    <h5 class="mb-3" style="font-weight: 700;">
                        <i class="fa-solid fa-user me-1" style="color: var(--gs-primary);"></i>Contact details
                    </h5>
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">Full name *</label>
                            <input type="text" name="name" class="form-control"
                                   value="{{ old('name', $prefill['name'] ?? '') }}" required maxlength="120">
                            @error('name')<div class="small text-danger mt-1">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Phone *</label>
                            <input type="tel" name="phone" class="form-control"
                                   value="{{ old('phone', $prefill['phone'] ?? '') }}"
                                   placeholder="03001234567" required maxlength="20">
                            @error('phone')<div class="small text-danger mt-1">{{ $message }}</div>@enderror
                        </div>
                    </div>
                </div>

                <div class="gs-card p-4 mb-3">
                    <h5 class="mb-3" style="font-weight: 700;">
                        <i class="fa-solid fa-location-dot me-1" style="color: var(--gs-primary);"></i>Delivery address
                    </h5>
                    <div class="row g-3">
                        <div class="col-md-12">
                            <label class="form-label">Area *</label>
                            <select name="area_id" class="form-select" required id="areaSelect">
                                <option value="">— Select your area —</option>
                                @foreach ($areas as $area)
                                    <option value="{{ $area->id }}"
                                            data-charge="{{ $area->delivery_charge }}"
                                            @selected(old('area_id', $prefill['area_id'] ?? null) == $area->id)>
                                        {{ $area->name }} ({{ $area->city }}) — Rs {{ number_format($area->delivery_charge, 0) }}
                                    </option>
                                @endforeach
                            </select>
                            @error('area_id')<div class="small text-danger mt-1">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-12">
                            <label class="form-label">Full address *</label>
                            <textarea name="address" class="form-control" rows="2" required maxlength="500"
                                      placeholder="House / Apt no, street, building, landmark">{{ old('address', $prefill['address'] ?? '') }}</textarea>
                            @error('address')<div class="small text-danger mt-1">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-12">
                            <div class="d-flex flex-wrap gap-2">
                                <button type="button" class="btn btn-outline-gs flex-grow-1" id="locBtn">
                                    <i class="fa-solid fa-location-crosshairs me-1"></i>
                                    <span id="locBtnText">Use my live location</span>
                                </button>
                                <button type="button" class="btn btn-outline-gs flex-grow-1" id="mapBtn">
                                    <i class="fa-solid fa-map-location-dot me-1"></i>
                                    <span id="mapBtnText">Pick on map</span>
                                </button>
                            </div>
                            <div id="pickerMap" class="hidden"></div>
                            <div class="text-muted small mt-1" id="locStatus"></div>
                            <input type="hidden" name="lat" id="latInput" value="{{ old('lat', $prefill['lat'] ?? '') }}">
                            <input type="hidden" name="lng" id="lngInput" value="{{ old('lng', $prefill['lng'] ?? '') }}">
                        </div>
                        <div class="col-12">
                            <label class="form-label">Notes for delivery (optional)</label>
                            <textarea name="customer_note" class="form-control" rows="2" maxlength="500"
                                      placeholder="e.g. Ring the doorbell twice">{{ old('customer_note', $prefill['customer_note'] ?? '') }}</textarea>
                        </div>
                    </div>
                </div>

                <div class="gs-card p-4">
                    <h5 class="mb-3" style="font-weight: 700;">
                        <i class="fa-solid fa-wallet me-1" style="color: var(--gs-primary);"></i>Payment method
                    </h5>
                    <div class="row g-2">
                        @foreach ([
                            'cod'      => ['Cash on Delivery', 'fa-truck'],
                            'cash'     => ['Jazz Cash', 'fa-money-bill-wave'],
                            // 'transfer' => ['Bank Transfer', 'fa-building-columns'],
                        ] as $val => $cfg)
                            <div class="col-md-4">
                                <label class="pay-option">
                                    <input type="radio" name="payment_method" value="{{ $val }}"
                                           @checked(old('payment_method', 'cod') === $val)>
                                    <i class="fa-solid {{ $cfg[1] }}" style="color: var(--gs-primary);"></i>
                                    <span class="fw-semibold">{{ $cfg[0] }}</span>
                                </label>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>

            {{-- ============ SUMMARY ============ --}}
            <div class="col-lg-5">
                <div class="gs-card p-4" style="position: sticky; top: 90px;">
                    <h5 class="mb-3" style="font-weight: 700;">Order Summary</h5>

                    {{-- Grocery items (admin will set price) --}}
                    @if (count($groceryItems ?? []))
                        <div class="mb-3" style="background:#fff8e1;border:1px solid #ffe082;border-radius:10px;padding:.75rem;">
                            <div class="fw-semibold mb-1" style="color:#b27300;">
                                <i class="fa-solid fa-pen-to-square me-1"></i>Custom grocery items
                            </div>
                            <div class="text-muted small mb-2">
                                Final price will be confirmed by admin via WhatsApp.
                            </div>
                            @foreach ($groceryItems as $g)
                                <div class="item-line">
                                    <div class="item-thumb">
                                        <i class="fa-solid fa-pen-to-square" style="color:#b27300;"></i>
                                    </div>
                                    <div class="flex-grow-1">
                                        <div class="fw-semibold">{{ $g['name'] }}</div>
                                        <div class="text-muted small">{{ $g['qty'] }} {{ $g['unit'] }}</div>
                                    </div>
                                    <div class="text-muted small">TBD</div>
                                </div>
                            @endforeach
                        </div>
                    @endif

                    {{-- Items --}}
                    <div class="mb-3">
                        @foreach ($items as $row)
                            @php
                                $img = $row->product->image
                                    ? \Illuminate\Support\Facades\Storage::url($row->product->image)
                                    : null;
                            @endphp
                            <div class="item-line">
                                <div class="item-thumb">
                                    @if ($img)<img src="{{ $img }}" alt="">@else<i class="fa-solid fa-image text-muted"></i>@endif
                                </div>
                                <div class="flex-grow-1">
                                    <div class="fw-semibold">{{ $row->product->name }}</div>
                                    <div class="text-muted small">
                                        {{ rtrim(rtrim((string) $row->qty, '0'), '.') }} {{ $row->product->unit }}
                                        × Rs {{ number_format($row->price, 0) }}
                                    </div>
                                </div>
                                <div class="fw-bold" style="color: var(--gs-primary);">
                                    Rs {{ number_format($row->line_total, 0) }}
                                </div>
                            </div>
                        @endforeach
                    </div>

                    {{-- Totals --}}
                    <div class="summary-row">
                        <span>Subtotal</span>
                        <span>Rs {{ number_format($totals['subtotal'], 0) }}</span>
                    </div>
                    @if ($totals['discount'] > 0)
                        <div class="summary-row">
                            <span>Discount @if($totals['coupon_code'])({{ $totals['coupon_code'] }})@endif</span>
                            <span class="text-success">- Rs {{ number_format($totals['discount'], 0) }}</span>
                        </div>
                    @endif
                    <div class="summary-row">
                        <span>Delivery</span>
                        <span><span id="deliveryDisplay">Rs 0</span></span>
                    </div>
                    <div class="summary-row total">
                        <span>Total</span>
                        <span style="color: var(--gs-primary);">Rs <span id="totalDisplay">{{ number_format($totals['subtotal'] - $totals['discount'], 0) }}</span></span>
                    </div>

                    <button type="submit" class="btn-primary-gs w-100 mt-3" id="placeBtn">
                        <i class="fa-solid fa-circle-check me-1"></i> Place Order
                    </button>

                    <div class="text-muted small text-center mt-2">
                        By placing this order you agree to our terms.
                    </div>
                </div>
            </div>
        </div>
    </form>
</div>
@endsection

@push('scripts')
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
<script>
(function () {
    const subtotal = {{ $totals['subtotal'] }};
    const discount = {{ $totals['discount'] }};

    const areaSelect = document.getElementById('areaSelect');
    const delDisplay = document.getElementById('deliveryDisplay');
    const totDisplay = document.getElementById('totalDisplay');

    function recalc() {
        const opt = areaSelect.options[areaSelect.selectedIndex];
        const charge = parseFloat(opt?.dataset?.charge || '0');
        delDisplay.textContent = 'Rs ' + Math.round(charge).toLocaleString();
        const total = Math.max(0, subtotal - discount + charge);
        totDisplay.textContent = Math.round(total).toLocaleString();
    }
    areaSelect.addEventListener('change', recalc);
    recalc();

    const latInput = document.getElementById('latInput');
    const lngInput = document.getElementById('lngInput');
    const status   = document.getElementById('locStatus');

    function setLatLng(lat, lng, label) {
        latInput.value = (+lat).toFixed(7);
        lngInput.value = (+lng).toFixed(7);
        status.innerHTML = '<i class="fa-solid fa-check text-success"></i> ' + label
            + ' (' + lat.toFixed(5) + ', ' + lng.toFixed(5) + ')';
    }

    /* Live location */
    document.getElementById('locBtn').addEventListener('click', () => {
        const txt = document.getElementById('locBtnText');
        if (!navigator.geolocation) { txt.textContent = 'Location not supported'; return; }
        txt.textContent = 'Getting location...';
        navigator.geolocation.getCurrentPosition(
            (pos) => {
                setLatLng(pos.coords.latitude, pos.coords.longitude, 'Live location');
                txt.textContent = '✓ Live location set';
                if (mapInstance) {
                    mapInstance.setView([pos.coords.latitude, pos.coords.longitude], 15);
                    if (marker) marker.remove();
                    marker = L.marker([pos.coords.latitude, pos.coords.longitude]).addTo(mapInstance);
                }
                window.GS.toast('Location captured');
            },
            (err) => { txt.textContent = 'Could not get location'; }
        );
    });

    /* Map picker (Leaflet) */
    let mapInstance = null;
    let marker = null;

    document.getElementById('mapBtn').addEventListener('click', () => {
        const mapEl = document.getElementById('pickerMap');
        const txt   = document.getElementById('mapBtnText');

        if (mapEl.classList.contains('hidden')) {
            mapEl.classList.remove('hidden');
            txt.textContent = 'Hide map';

            if (!mapInstance) {
                // Default centre: Karachi
                let startLat = parseFloat(latInput.value || '24.8607');
                let startLng = parseFloat(lngInput.value || '67.0011');
                mapInstance = L.map('pickerMap').setView([startLat, startLng], 13);
                L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                    attribution: '&copy; OpenStreetMap',
                    maxZoom: 19,
                }).addTo(mapInstance);

                if (latInput.value && lngInput.value) {
                    marker = L.marker([startLat, startLng]).addTo(mapInstance);
                }

                mapInstance.on('click', (e) => {
                    if (marker) marker.remove();
                    marker = L.marker(e.latlng).addTo(mapInstance);
                    setLatLng(e.latlng.lat, e.latlng.lng, 'Picked on map');
                });

                // Fix sizing inside hidden container
                setTimeout(() => mapInstance.invalidateSize(), 100);
            } else {
                setTimeout(() => mapInstance.invalidateSize(), 100);
            }
        } else {
            mapEl.classList.add('hidden');
            txt.textContent = 'Pick on map';
        }
    });

    /* Disable submit on click */
    document.getElementById('checkoutForm').addEventListener('submit', () => {
        const btn = document.getElementById('placeBtn');
        btn.disabled = true;
        btn.innerHTML = '<i class="fa-solid fa-spinner fa-spin me-1"></i> Placing order...';
    });
})();
</script>
@endpush
