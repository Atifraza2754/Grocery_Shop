# Frontend (Customer Site) Setup

Customer-facing storefront. Bootstrap 5 + Font Awesome, green theme matching the admin. Pulls live data from the same DB the admin writes to.

---

## What's been built

### Routes (all under `/`)

| URL | What |
|---|---|
| `GET /` | Home — hero, search, category tabs, product grid |
| `GET /products/{slug}` | Product detail — gallery, items-included, qty stepper, related |
| `GET /cart` | Cart — items, qty +/-, remove, coupon apply, summary |
| `POST /cart/add` `update` `remove` `coupon` | AJAX cart endpoints |
| `GET /cart/count` | AJAX live cart count |
| `GET /checkout` | Checkout form — name/phone/address/area/payment + live total |
| `POST /checkout` | Creates Customer + Order in admin DB |
| `GET /order/{orderNo}` | Order success/details with status timeline |
| `GET /track` + `POST /track` | Track orders by phone |

### Architecture

```
resources/views/site/
├── layouts/app.blade.php          (master: header, search, cart icon, footer, toasts, AJAX helpers)
├── partials/product-card.blade.php
├── home.blade.php
├── products/show.blade.php
├── cart.blade.php
├── checkout.blade.php
├── order-success.blade.php
└── track.blade.php

app/
├── Services/CartService.php       (session-based cart, single source of truth)
├── Http/Controllers/Site/
│   ├── HomeController.php
│   ├── ProductController.php
│   ├── CartController.php         (AJAX endpoints)
│   ├── CheckoutController.php     (places real Order via admin pipeline)
│   └── OrderController.php
└── Providers/AppServiceProvider.php  (CartService singleton + view composer for cart count)
```

### How it connects to admin

When a customer places an order:

1. `Customer::findOrCreateByPhone($phone, ...)` — same model as admin
2. `Order::create(...)` — same model. Auto-generates `order_no`, sets status `pending`
3. Order items snapshot product fields (sku, name, unit, price, qty)
4. Coupon applied through `Coupon::validateAgainst($subtotal)` — same as admin
5. `recalculateTotals()` — same as admin
6. **Auto-assigns ambassador by area** — if any ambassador covers that area, gets attached
7. Cart cleared, redirected to success page
8. Order shows up in `/admin/orders` instantly with `placed_by_user_id = NULL` (= customer-placed)

When admin walks the order through to delivered → commission auto-generated for the ambassador (Phase 5 hooks fire as normal).

### Key UX

- Cart count badge in header — live updates via AJAX, persists across pages
- Toast notifications for add/remove/coupon — top-right corner
- Search + category tabs on home — client-side filtering for instant feedback
- Live total calculation on checkout — when area changes, delivery + total update without page reload
- "Use my live location" button — captures lat/lng (uses browser geolocation API)
- Order timeline on success page — visual progress through 5 stages
- Track-by-phone — public lookup using customer_phone

---

## Run these commands

```bash
php artisan optimize:clear
php artisan storage:link
```

(`storage:link` was already run in Phase 0 — re-running is harmless. It exposes uploaded product images at `/storage/...`)

No migrations or seeders this phase.

---

## Verify end-to-end

### 1. Make sure you have data

If you haven't yet:
- Admin → Categories: 4 already seeded
- Admin → Products: create at least 4 products with images, in different categories
- Admin → Coupons: create one (e.g. `WELCOME10` = 10% off)
- Admin → Areas: 8 already seeded
- Admin → Ambassadors / Plans / Stock: optional but tests the full flow

### 2. Visit `http://127.0.0.1:8000/` (or your Laragon URL)

You should see:
- Green branded header with cart icon (count = 0)
- Hero card "Order Groceries"
- Search bar
- Category tabs (All + your seeded categories)
- Product grid showing your active products

### 3. Customer journey

1. Click any product card → product detail page opens
2. See: cover image (with thumbnail strip if gallery exists), price, in-stock pill, "What's inside" if items repeater filled, qty stepper +/-, Add to Cart button
3. Click "Add to Cart" → toast "X added to cart", cart count goes to 1
4. Hit back, click another product → add 2 of it
5. Click cart icon (top right) → cart page lists both items with thumbnails
6. Use +/- buttons → line totals update live, cart count updates
7. Enter your coupon code → click Apply → "Coupon X applied" toast, discount row appears
8. Click "Proceed to Checkout"
9. Fill in:
   - Name: `Test Customer`
   - Phone: `03009999999`
   - Area: pick `DHA Phase 5` (delivery charge auto-shows in dropdown + summary)
   - Address: `Test address, House 1`
   - Click "Use my live location" → browser asks permission → captures lat/lng
   - Notes: `Ring twice`
   - Payment: keep COD
10. Watch right rail: Total updates as you change area
11. Click "Place Order" → green success banner with `ORD-YYYYMMDD-NNNN`
12. See timeline (Pending lit), items list, delivery info, bill breakdown
13. Click "Track this order" → see your order in the list

### 4. Verify in admin

1. Open `/admin/orders` in another tab
2. Your order should be at the top with status **Pending**, the customer name, the area, the total
3. Click into it — items match, address matches, ambassador auto-assigned (if one covers that area), coupon applied
4. Click "Confirm order" → "Start preparing" → "Out for delivery" → "Mark delivered"
5. Refresh the customer's order tracking page → timeline progresses

### 5. Verify customer record

`/admin/customers` → should show `Test Customer` (03009999999) with `total_orders: 1`, `total_spend` matching your test order, segment `🟢 Active`.

---

## Files referenced from your original design

Your `resources/views/frontend/home.blade.php` is preserved untouched — the new working customer site lives at `resources/views/site/*` with the same color scheme and visual language as your design.

If you want to wipe the old file:
```bash
rm resources/views/frontend/home.blade.php
```

---

## Edge cases handled

- Empty cart → checkout redirects to cart with a flash message
- Customer with no orders yet placing first order → customer auto-created
- Returning customer (same phone) → existing customer record updated with new address/area
- Cancelled product (admin sets is_active=false) → removed from cart silently on next view
- Coupon expires/disabled while in cart → validation re-checked on every page load and at checkout
- No ambassador in area → order is placed without ambassador (admin can assign manually)

---

## What's NOT here yet (per your "in advance" tagging)

- Customer login / accounts (currently just phone-based)
- WhatsApp auto-message on order placement → Phase 8
- Abandoned cart tracking → next phase
- AI chat-prompt filters → next phase

---

## Next ideas

If you want to extend this:
- **Featured carousel** on home (use the `is_featured` toggle that's already on Product)
- **WhatsApp button** that opens chat with you when a customer wants help
- **Reorder** action on order details page (re-fills cart from a previous order)
- **Save addresses** for returning customers (look up by phone, auto-fill checkout)

Tell me which one you want and I'll add it.
