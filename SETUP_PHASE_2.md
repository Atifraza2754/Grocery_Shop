# Phase 2 — Coupon Management Setup

Quick phase. One table, one model, one Filament resource — but the model already has all the order-side validation logic Phase 3 will need.

---

## What's been built

### Database

`coupons` table:

| Column | Type | Purpose |
|---|---|---|
| `name` | string | Display name (e.g. "New Year Sale") |
| `code` | string (unique, uppercase) | What customers type at checkout |
| `type` | enum: `percent` / `flat` | % off vs Rs off |
| `value` | decimal | Discount amount |
| `min_order_amount` | decimal nullable | Minimum subtotal to qualify |
| `max_discount_amount` | decimal nullable | Cap on % discount |
| `usage_limit` | int nullable | Total uses across all customers |
| `usage_per_customer` | int nullable | Per-phone limit |
| `used_count` | int | Auto-incremented when an order uses it |
| `starts_at` / `expires_at` | datetime nullable | Validity window |
| `description` | text | Optional internal note |
| `is_active` | boolean | On/off toggle |
| soft-deletes | | |

### Model — `App\Models\Coupon`

Useful methods (will be reused in Phase 3 — Orders):

- `Coupon::generateCode(8)` — returns a random unique code (no I/O/0/1 to avoid confusion)
- `$coupon->isValid()` — quick checks (active, not expired, has started, under limit)
- `$coupon->validateAgainst($subtotal, $customerId, $usesSoFar)` — full validation, returns `['ok' => bool, 'message' => string, 'discount' => float]`
- `$coupon->calculateDiscount($subtotal)` — returns the discount value, capped at subtotal
- `Coupon::available()` scope — filter to currently-usable coupons
- `$coupon->value_label` — formatted "10%" or "Rs 100"
- `$coupon->status_label` — Active / Scheduled / Expired / Used up / Inactive

### Filament Resource — Sales → Coupons

Form sections:

1. **Coupon details** — name, code (with sparkle button to auto-generate), type (% or flat), value, description
2. **Conditions** (collapsible) — min order, max discount cap (only shown for %), total usage limit, per-customer limit, starts/expires datetime pickers
3. **Status** — active toggle

Table:

- Code (badge, copy-to-clipboard on click)
- Name, type, value
- Used X / Y format
- Expires-at column (red text when expired)
- Status badge (Active/Scheduled/Expired/Used up/Inactive)
- Per-row **Turn on / Turn off** action (one-click)
- Filters: by type, active, expired, currently available

---

## Run these commands

In your Laragon terminal:

```bash
php artisan migrate
php artisan optimize:clear
```

No new seeder this phase — coupons are user-created.

---

## Verify

1. Hard-refresh `/admin`
2. Sidebar: under **Sales** group → **Coupons**
3. Click **New coupon**:
   - Name: `Welcome Bonus`
   - Click the ✨ icon next to Code → it generates a code like `K7XQRMVH`
   - Type: `Percentage off (%)`, Value: `15`
   - Min order amount: `1000`, Max discount cap: `300`
   - Usage limit: `100`, Per-customer: `1`
   - Save
4. Try creating a flat one too:
   - Name: `Flat 100 Off`, Code: `FLAT100`, Type: `Flat`, Value: `100`, Min order: `500`
5. List view should show:
   - Welcome Bonus → `K7XQRMVH` badge → 15% → "0 / 100" → status `Active`
   - Flat 100 Off → `FLAT100` badge → Rs 100 → "0" → status `Active`
6. Click the code badge → copies to clipboard.
7. Click the **Turn off** action → status flips to `Inactive`. Click again to re-enable.

---

## Next: Phase 3 — Order Management

This is the big one (5–7 days):

- Areas (delivery charges)
- Customers
- Orders + Order Items + Status logs
- Manual order create screen — search by phone, add products, apply coupon
- Status workflow with audit log
- "Copy order details" formatted text for WhatsApp

Reply with **"phase 3"** when ready, or anything you want tweaked first.
