# Phase 3 — Order Management Setup

The biggest module so far. Skips the "in advance" WhatsApp auto-messaging — that's Phase 8.

---

## What's been built

### Database (5 new tables)

| Table | Purpose |
|---|---|
| `areas` | Delivery zones with per-area charge |
| `customers` | Phone-keyed (unique) with rolling stats: `total_orders`, `total_spend`, `last_order_at` |
| `orders` | Snapshots customer name/phone/address so editing customer doesn't change history |
| `order_items` | Snapshots SKU, name, unit, price — survives product deletion |
| `order_status_logs` | Full audit trail of every status change |

### Models

- **`Area`** — `hasMany` customers + orders, active scope
- **`Customer`** — `findOrCreateByPhone()`, `recomputeStats()` rolls up totals from delivered orders
- **`Order`** — auto-generates `order_no` (`ORD-YYYYMMDD-NNNN`), 6-step status workflow with `changeStatus($newStatus, $note)`, `recalculateTotals()` does subtotal/discount/delivery/total math, `toShareableText()` produces the WhatsApp-formatted text
- **`OrderItem`** — auto-snapshots product fields + recomputes `line_total` on save
- **`OrderStatusLog`** — append-only audit trail

Money flow on order create:

1. Items repeater inserts `order_items` (line_total auto-computed in model)
2. `recalculateTotals()` reads sum of line_totals, applies coupon (if valid for subtotal), adds area's delivery charge → writes `subtotal`, `discount`, `delivery_charge`, `total`
3. Coupon's `used_count` is incremented (decremented if order is cancelled)
4. Customer's stats roll up

### Filament resources

- **Operations → Areas** — name, city, delivery charge, sort order, active
- **People → Customers** — basic CRUD; phone is unique and copyable; shows total orders, total spend, last order. Will be enhanced in Phase 6 with order history & segmentation.
- **Sales → Orders** — the heavyweight:
  - **Tabs at top** of list: All / Pending / Confirmed / Preparing / Out for delivery / Delivered / Cancelled (with live counts)
  - **Filters**: status, payment status, area, "Placed today"
  - **Navigation badge** = pending + confirmed orders count
  - **Create form** (3-column layout):
    - Customer search by phone — selecting a customer auto-fills name/phone/address/area. "Create new" inline form for walk-ins.
    - Items repeater — pick product → SKU/name/unit/price auto-fill, qty/price editable, live line-total
    - Right rail: Area (auto-fills delivery charge), Coupon select, **live Summary** (subtotal/discount/delivery/total), status & payment
  - **Edit page** — same form + status workflow buttons in the header:
    - "Confirm order" (visible when pending)
    - "Start preparing" (when confirmed)
    - "Out for delivery" (when preparing/confirmed)
    - "Mark delivered" (when out_for_delivery/preparing/confirmed)
    - "Cancel order" (any non-final state, requires note)
    - Each opens a confirm modal with optional note → recorded in audit log
  - **"Copy text" action** — opens a modal with WhatsApp-formatted order summary + one-click copy button (uses Alpine + clipboard API)
  - **Status history** relation manager below the form — shows full audit trail (from → to, who, when, note)

### Sample seed data

8 sample Karachi areas (DHA Phase 1, Phase 2, Phase 5, Clifton, Gulshan, Bahadurabad, PECHS, North Nazimabad) with delivery charges 100–250.

---

## Run these commands

In Laragon terminal:

```bash
php artisan migrate
php artisan db:seed --class=AreasSeeder
php artisan optimize:clear
```

---

## Verify

1. Hard-refresh `/admin`. New sidebar items:
   - **Sales** → Orders (with badge for pending count once you have orders)
   - **People** → Customers
   - **Operations** → Areas
2. **Areas** — should list 8 seeded Karachi areas.
3. **Orders → New order:**
   - Search customer by phone → empty list (no customers yet). Click "Create new".
   - Fill: name `Atif Razait`, phone `03001234567`, area `DHA Phase 5`. Save.
   - Add 2 items: pick a product, qty `2`, watch line total update live.
   - Pick area `DHA Phase 5` → delivery charge auto-fills `Rs 150`.
   - If you have an active coupon — pick it from the Coupon dropdown. Live summary on the right shows discount.
   - Save. You're redirected to the Edit page.
4. On the Edit page:
   - Order number auto-generated like `ORD-20260502-0001`.
   - Header buttons: "Confirm order", "Cancel order", "Copy text".
   - Click **Confirm order** → modal opens → optional note → confirm → status flips to `Confirmed`, "Start preparing" button appears.
   - Click through the workflow: Confirmed → Preparing → Out for delivery → Delivered.
   - Scroll down → **Status history** widget shows every transition with timestamp + user.
5. **Copy text** action:
   - Opens a modal with WhatsApp-ready text:
     ```
     🛒 Order #ORD-20260502-0001
     Status: Confirmed

     👤 Customer
     Name: Atif Razait
     Phone: 03001234567
     ...

     📦 Items
     • Onion - Chopped — 2 piece × Rs 200.00 = Rs 400.00
     ...

     💰 Bill
     Subtotal: Rs 400.00
     Delivery: Rs 150.00
     Total: Rs 550.00
     ```
   - Click "Copy to clipboard" → button changes to "Copied!" → paste into WhatsApp.
6. **Customers** → after the order, the customer should now show: 1 order, total spend matching, last-order-at populated.
7. **List view** → top tabs filter by status with badges.

---

## What's NOT in Phase 3 (skipped per "in advance")

- WhatsApp auto-messaging on order create / status change → Phase 8
- Customer-facing checkout / website-driven order flow → Phase 9

Manual orders (admin enters phone orders) is fully working now.

---

## Next: Phase 4 — Vendor & Inventory

Reply with **"phase 4"** when ready.
