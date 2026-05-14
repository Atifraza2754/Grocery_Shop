<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Grocery Shop') — {{ config('app.name') }}</title>

    {{-- Bootstrap + Font Awesome (matches the design) --}}
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

    <style>
        :root {
            --gs-primary:        #2e7d32;
            --gs-primary-dark:   #1b5e20;
            --gs-primary-soft:   #e8f5e9;
            --gs-text:           #2c3e50;
            --gs-muted:          #6c757d;
            --gs-border:         #e9ecef;
            --gs-bg:             #f8f9fa;
        }

        * { box-sizing: border-box; }
        html, body { height: 100%; }
        body {
            background: var(--gs-bg);
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            color: var(--gs-text);
            display: flex;
            flex-direction: column;
        }
        main { flex: 1; }

        /* ========= HEADER ========= */
        .site-header {
            background: #fff;
            border-bottom: 1px solid var(--gs-border);
            position: sticky; top: 0; z-index: 100;
        }
        .brand {
            font-weight: 800;
            color: var(--gs-primary);
            font-size: 1.4rem;
            text-decoration: none;
            letter-spacing: -0.01em;
        }
        .brand i { margin-right: 6px; }

        /* Allow brand to shrink on small screens and truncate to keep header on one line */
        .site-header .brand {
            min-width: 0;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
            display: inline-block;
        }

        .btn-cart {
            background: var(--gs-primary);
            color: #fff;
            border: none;
            border-radius: 999px;
            padding: 8px 18px;
            font-weight: 600;
            transition: all .25s ease;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }
        .btn-cart:hover { background: var(--gs-primary-dark); color: #fff; transform: translateY(-1px); }
        .cart-badge {
            background: #fff;
            color: var(--gs-primary);
            border-radius: 999px;
            padding: 1px 9px;
            font-weight: 700;
            font-size: 0.85rem;
            min-width: 26px;
            text-align: center;
        }

        /* ========= BUTTONS ========= */
        .btn-primary-gs {
            background: var(--gs-primary); border: none; color: #fff; font-weight: 600;
            border-radius: 10px; padding: 10px 20px; transition: all .2s ease;
        }
        .btn-primary-gs:hover { background: var(--gs-primary-dark); color: #fff; }
        .btn-outline-gs {
            background: transparent; border: 1.5px solid var(--gs-primary); color: var(--gs-primary);
            font-weight: 600; border-radius: 10px; padding: 9px 18px; transition: all .2s;
        }
        .btn-outline-gs:hover { background: var(--gs-primary); color: #fff; }

        /* ========= CARDS ========= */
        .gs-card {
            background: #fff; border: 1px solid var(--gs-border);
            border-radius: 14px; padding: 1.25rem;
        }

        /* ========= FOOTER ========= */
        .site-footer {
            background: #fff; border-top: 1px solid var(--gs-border);
            padding: 0.5rem 0; margin-top: 2rem; color: var(--gs-muted);
            font-size: 1rem;
        }

        /* ========= TOAST ========= */
        .gs-toast-wrap {
            position: fixed; top: 90px; right: 20px; z-index: 2000;
            display: flex; flex-direction: column; gap: 10px;
            pointer-events: none;
        }
        .gs-toast {
            min-width: 240px;
            background: #fff;
            border-left: 4px solid var(--gs-primary);
            border-radius: 8px;
            padding: 12px 16px;
            box-shadow: 0 6px 18px rgba(0,0,0,0.10);
            font-size: 0.92rem;
            display: flex; align-items: center; gap: 10px;
            opacity: 0; transform: translateX(20px);
            transition: opacity .25s ease, transform .25s ease;
            pointer-events: auto;
        }
        .gs-toast.show { opacity: 1; transform: translateX(0); }
        .gs-toast.error  { border-left-color: #dc3545; }
        .gs-toast.warn   { border-left-color: #ffc107; }
        .gs-toast i { font-size: 1.1rem; color: var(--gs-primary); }
        .gs-toast.error i { color: #dc3545; }
        .gs-toast.warn  i { color: #ffc107; }

        /* small responsive tweaks */
        @media (max-width: 575.98px) {
            .brand { font-size: 1.15rem; }
            .btn-cart { padding: 7px 14px; font-size: 0.9rem; }
            .trackorder {
               margin-right: -0.5rem!important;
            }
        }
        
    </style>

    @stack('styles')
</head>
<body>

{{-- ======================== HEADER ======================== --}}
<header class="site-header">
    <div class="container py-3">
        <div class="d-flex align-items-center justify-content-between gap-3 flex-nowrap">
                <a href="{{ route('site.home') }}" class="brand flex-grow-1 me-2 text-truncate" style="min-width:0;">
                <i class="fa-solid fa-leaf"></i>{{ config('app.name', 'Grocery Shop') }}
            </a>

                <div class="d-flex align-items-center gap-2 flex-shrink-0">
                <a href="{{ route('site.track') }}" class="btn btn-light d-inline-flex align-items-center me-2 trackorder"
                   style="border-radius: 999px; font-size: 0.9rem;">
                    <i class="fa-solid fa-truck me-1"></i> Track order
                </a>

                <a href="{{ route('site.cart') }}" class="btn-cart">
                    <i class="fa-solid fa-basket-shopping"></i>
                    <span class="d-none d-sm-inline">Cart</span>
                    <span class="cart-badge" id="gsCartCount">{{ $gsCartCount ?? 0 }}</span>
                </a>
            </div>
        </div>
    </div>
</header>

{{-- ======================== TOASTS ======================== --}}
<div class="gs-toast-wrap" id="gsToastWrap"></div>

{{-- ======================== MAIN ======================== --}}
<main>
    @yield('content')
</main>

{{-- ======================== FOOTER ======================== --}}
<footer class="site-footer">
    <div class="container d-flex flex-wrap gap-2 align-items-center justify-content-center">
        <div>
            <strong style="color: var(--gs-primary);">
                <i class="fa-solid fa-leaf"></i> {{ config('app.name') }}
            </strong>
            <span class="ms-2">&copy; {{ date('Y') }} rights are reserved.</span>
        </div>

    </div>
</footer>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

{{-- Global helpers (toasts + AJAX cart) --}}
<script>
    window.GS = {
        csrf:  document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
        urls:  {
            cartAdd:           @json(route('site.cart.add')),
            cartUpdate:        @json(route('site.cart.update')),
            cartRemove:        @json(route('site.cart.remove')),
            cartCoupon:        @json(route('site.cart.coupon')),
            cartCount:         @json(route('site.cart.count')),
            cartGroceryAdd:    @json(route('site.cart.grocery.add')),
            cartGroceryRemove: @json(route('site.cart.grocery.remove')),
        },

        toast(message, type = 'success') {
            const wrap = document.getElementById('gsToastWrap');
            const el = document.createElement('div');
            el.className = 'gs-toast ' + (type === 'error' ? 'error' : type === 'warn' ? 'warn' : '');
            const icon = type === 'error' ? 'circle-exclamation'
                       : type === 'warn'  ? 'triangle-exclamation' : 'circle-check';
            el.innerHTML = `<i class="fa-solid fa-${icon}"></i><span>${message}</span>`;
            wrap.appendChild(el);
            requestAnimationFrame(() => el.classList.add('show'));
            setTimeout(() => {
                el.classList.remove('show');
                setTimeout(() => el.remove(), 300);
            }, 2400);
        },

        async post(url, body = {}) {
            const res = await fetch(url, {
                method: 'POST',
                headers: {
                    'Content-Type':   'application/json',
                    'Accept':         'application/json',
                    'X-CSRF-TOKEN':   this.csrf,
                    'X-Requested-With': 'XMLHttpRequest',
                },
                body: JSON.stringify(body),
            });
            const data = await res.json().catch(() => ({}));
            return { ok: res.ok, status: res.status, data };
        },

        setCartCount(n) {
            const el = document.getElementById('gsCartCount');
            if (el) el.textContent = n;
        },

        async addToCart(productId, qty = 1) {
            const { ok, data } = await this.post(this.urls.cartAdd, { product_id: productId, qty });
            if (ok) {
                this.setCartCount(data.count);
                this.toast(data.message || 'Added to cart');
            } else {
                this.toast(data.message || 'Could not add to cart', 'error');
            }
            return { ok, data };
        },
    };
</script>

<!-- Product modal (used for product details loaded via AJAX) -->
<div class="modal fade" id="productModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-xl modal-fullscreen-sm-down">
        <div class="modal-content">
            <div class="modal-body p-0" id="productModalContent">
                <!-- injected content -->
            </div>
        </div>
    </div>
</div>

@stack('scripts')

</body>
</html>
