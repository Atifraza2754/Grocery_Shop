# Order Management v2 — Setup

Adds the new requirements without breaking the original flow:

1. **Grocery tab** on customer side — customers type custom item name + qty
2. **Admin pricing flow** — admin opens order, sets price/qty for grocery items, saves
3. **WhatsApp notifications** — auto-sent to admin + customer 5 seconds after order placement
4. **Map picker** for live location (Leaflet, free, no API key)
5. **Quick view modal** + **quick confirm** action on admin orders list
6. **Pricing badge** on admin orders list showing "Needs pricing" when grocery items are unpriced

All changes are **additive** — old products-only orders work exactly as before.

---

## Run these commands

```bash
php artisan migrate
php artisan optimize:clear
php artisan config:clear
```

The migration adds `is_grocery_request` (boolean, default `false`) to `order_items`.
Existing rows are unaffected.

---

## WhatsApp configuration

Add these to your `.env` (already added as blanks):

```env
WHATSAPP_TOKEN=
WHATSAPP_PHONE_ID=
WHATSAPP_ADMIN_PHONE=
WHATSAPP_API_VERSION=v20.0
WHATSAPP_DEFAULT_COUNTRY=92
WHATSAPP_SEND_DELAY_SEC=5
```

### Behaviour

- **Blank credentials** → messages are written to `storage/logs/laravel.log` instead of being sent. Order placement always succeeds.
- **Filled in** → real WhatsApp messages sent via Meta's Cloud API.

### Getting Meta WhatsApp Cloud API credentials (free tier)

1. Visit `https://developers.facebook.com/` and create a Meta app
2. Add the WhatsApp product to your app
3. Get your test phone number and access token under WhatsApp → Getting Started
4. Copy:
   - `Phone number ID` → `WHATSAPP_PHONE_ID`
   - `Temporary access token` (or generate permanent) → `WHATSAPP_TOKEN`
5. Set your own admin WhatsApp number (with country code, no `+`) → `WHATSAPP_ADMIN_PHONE`
   - e.g. `923001234567`
6. For testing on the free tier, you must add recipient numbers to "test recipients" in the Meta dashboard

### Queue worker (required for the 5-second delay)

The job is queued and runs after a delay. For development, two options:

**Option A (simplest) — sync queue:**
In `.env` set:
```env
QUEUE_CONNECTION=sync
```
Messages send immediately when the order is placed (delay is ignored).

**Option B — proper queue with delay:**
Keep `QUEUE_CONNECTION=database` (current default), then in a separate terminal run:
```bash
php artisan queue:work
```
Leave it running. Jobs will fire 5 seconds after order placement.

---

## What's been added

### Database
- `order_items.is_grocery_request` (boolean, default false) — flags customer-typed items

### Models
- `Order::needsPricing()` — true if any item is grocery + price ≤ 0
- `Order::pricingStatusLabel()` — "Needs pricing" / "Priced"
- `Order::mapsUrl()` — Google Maps URL from lat/lng (or null)
- `OrderItem::isGroceryRequest()`, `OrderItem::needsPricing()`

### Services
- `App\Services\WhatsAppService` — Meta Cloud API client with logging fallback
- `App\Services\CartService` — extended with `addGroceryItem()`, `removeGroceryItem()`, `groceryItems()`

### Jobs
- `App\Jobs\SendOrderWhatsApp` — queued job, sends to `'admin'` or `'customer'` audience, builds the message format

### Customer site
- **Home → Grocery (custom)** tab — text input for name + qty + unit, list of added items, "View cart & checkout" button
- **Cart page** → grocery items shown in their own yellow-bordered card with "Needs pricing" badge
- **Checkout** → grocery section in summary marked "TBD" for price; map picker button opens an embedded Leaflet map under the address fields; clicking the map drops a marker and fills the lat/lng

### Admin
- **Orders list** new columns: `Pricing` badge (yellow when "Needs pricing")
- **Orders list** new actions: **View** (modal — customer #, area, copyable Google Maps link, total bill, items list, custom-item highlight), **Confirm** (one-click confirm for pending orders, with extra warning if any items still need pricing)
- **Orders edit** form items repeater now allows editing **name** of any item (so admin can refine grocery item names too) and the **price** field for grocery items shows "Set price" placeholder. Totals auto-recalc on save.

---

## Verify end-to-end

### 1. Customer places a grocery order
1. Visit `/`
2. Click **Grocery (custom)** tab
3. Add: `Chicken kabab`, qty `2`, unit `pack` → click **Add**
4. Add: `Daal Chana`, qty `1`, unit `kg` → click **Add**
5. (Optional) also add a regular product from another tab
6. Click cart icon → cart page shows: yellow "Custom grocery items" card on top + regular products below (if any)
7. Click **Proceed to Checkout** → checkout summary shows custom items with "TBD" prices
8. Click **Pick on map** → map opens → click your location → lat/lng auto-fill
9. Fill name/phone/area/address → **Place Order**
10. Order success page opens

### 2. Verify WhatsApp dispatch
- If credentials blank: check `storage/logs/laravel.log` for `WhatsApp (NOT CONFIGURED, logged only)` entries with the formatted message — there should be 2 (admin + customer).
- If credentials set + queue worker running: WhatsApp messages arrive on both phones ~5 seconds after placing the order.

### 3. Admin handles the order
1. `/admin/orders` → your new order at top with **Pricing: Needs pricing** badge
2. Click **View** → modal opens showing customer phone (with copy button), area, copyable Google Maps link, total bill, items list with custom items highlighted in amber
3. Click **Edit** → items repeater shows your grocery items with editable name + qty + empty price field
4. Set prices for both grocery items, save → totals auto-recalc, "Needs pricing" badge disappears
5. Back on list → click **Confirm** → quick confirm modal → status flips to Confirmed

### 4. Old flow not broken
- Place a normal product-only order → no grocery section appears, no "Needs pricing" badge, all admin actions work as before.

---

## Tweakables

- **WhatsApp delay** — set `WHATSAPP_SEND_DELAY_SEC=10` etc. in `.env`
- **Default country code** for phone normalisation — set `WHATSAPP_DEFAULT_COUNTRY=92`
- **VIP threshold** for customer module — already has segmentation in Phase 6

---

## Known limitations

- WhatsApp Cloud API free tier has rate limits (~1000 conversations/month)
- The 5-second delay needs `php artisan queue:work` running in a terminal (or set `QUEUE_CONNECTION=sync` for instant send)
- Map picker uses OpenStreetMap tiles — works without an API key but please respect their usage policy in production
