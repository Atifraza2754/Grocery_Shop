# Phase 5 — Brand Ambassador System Setup

The biggest module — your micro-warehouse model.

Skipped per "in advance" rule:
- Separate ambassador login dashboard
- Low-stock alerts

---

## What's been built

### Database (7 migrations)

| Table | Purpose |
|---|---|
| `commission_plans` | Named plans like "Standard 10%", "Premium 15%" |
| `ambassadors` | Linked to area + building + plan; soft deletes |
| `stock_items` | Warehouse items ambassadors hold (kofta, kabab, etc.) — separate from products |
| `ambassador_stock` | Current balance per (ambassador, item) — denormalized for speed |
| `stock_movements` | Audit trail of every assign/release/adjust — auto-applies to balance |
| `commissions` | One per (ambassador, order); auto-generated on delivery |
| **alter** `orders` | Adds `ambassador_id` (nullable, FK) + index |

### Models

- **`CommissionPlan`** — name, percent, active
- **`Ambassador`** — `area`, `building`, `plan` belongsTo's; aggregates: `orders_handled_count`, `revenue_generated`, `commission_total`, `commission_paid`, `commission_pending`. `recordStockMovement()` helper creates the movement, which auto-updates balance.
- **`StockItem`** — name + unit; `total_qty` accessor sums across all ambassadors
- **`AmbassadorStock`** — denormalized (ambassador × item) balance row, unique constraint
- **`StockMovement`** — auto-applies signed quantity (`assign` +, `release` −, `adjust` signed) to `ambassador_stock` on create. Reverses on delete.
- **`Commission`** — generated per order, snapshots base + percent + amount, `markPaid($method, $note)` / `markCancelled()` methods

### Order integration

When `Order::changeStatus('delivered')`:
- Reads `order.ambassador_id` and ambassador's plan
- Creates a `Commission` row: `base_amount = subtotal − discount`, `amount = base × percent / 100`, status `pending`
- Skips if no ambassador, no plan, or commission already exists

When `Order::changeStatus('cancelled')`:
- Marks any pending commissions for that order as `cancelled`

### Filament resources — new "Ambassadors" navigation group

| Resource | What |
|---|---|
| **Ambassadors** | CRUD with name/phone/email/area/building/plan/active. List shows orders delivered, pending payout (yellow if >0), paid out. Filters by area, plan, active. **Header actions**: "Assign stock" (opens modal: pick item + qty + note), "Release stock" (only shows items they currently have, with current qty in dropdown). Below the form, three relation managers: **Current stock**, **Commissions** (with single + bulk "Mark paid"), **Assigned orders**. |
| **Commission Plans** | Simple CRUD |
| **Stock Items** | Simple CRUD; list shows total qty across all ambassadors |
| **Stock Movements** | Read-mostly audit trail with filters by ambassador/item/type. Manual creation supported. Deleting a movement reverses its effect. |
| **Commissions** | List with tabs (All / Pending / Paid / Cancelled), single + bulk "Mark paid" actions. Nav badge = pending count. |

### Order resource enhancements

- New "Assigned ambassador" select in the Delivery section
- Picking an Area auto-suggests an ambassador covering that area
- New "Ambassador" column + filter on the Orders table

---

## Run these commands

```bash
php artisan migrate
php artisan optimize:clear
```

(No seeder this phase.)

---

## End-to-end verification

1. Hard-refresh `/admin`. New nav group **"Ambassadors"** between People and Operations, containing:
   - Ambassadors
   - Commission Plans
   - Stock Items
   - Stock Movements
   - Commissions

2. **Create a commission plan** → Ambassadors → Commission Plans → New
   - Name: `Standard 10%`, Percent: `10`. Save.

3. **Create stock items** → Ambassadors → Stock Items → New
   - `Frozen Kofta - Chicken`, unit: `piece`. Save.
   - `Frozen Kabab - Beef`, unit: `piece`. Save.

4. **Create an ambassador** → Ambassadors → Ambassadors → New
   - Name: `Hassan Ali`, phone: `03001234567`, area: `DHA Phase 5`, building: `Marina Tower`, plan: `Standard 10%`. Save.

5. **Assign stock to that ambassador** → on the edit page header, click **"Assign stock"**:
   - Pick `Frozen Kofta - Chicken`, qty: `20`. Save → notification "Stock assigned".
   - Click **"Assign stock"** again, pick `Frozen Kabab - Beef`, qty: `15`. Save.
   - Scroll down → **Current stock** relation manager shows both items with their qty.
   - Visit **Stock Movements** → 2 audit rows, type `Assigned` (green +20 piece, +15 piece), recorded by Super Admin.

6. **Release stock** → back on ambassador edit, click **"Release stock"**:
   - Dropdown only shows items he currently has, with "(have: 20 piece)" hints.
   - Pick Kofta, qty `5`, note: `Sold 5 koftas at door`. Save.
   - Current stock now shows Kofta = 15 piece (was 20).
   - Stock Movements has a new red `Released` row.

7. **Create an order assigned to this ambassador** → Sales → Orders → New
   - Pick a customer (or create new)
   - Add an item from Products
   - Pick Area: `DHA Phase 5` → notice the **Ambassador** field auto-selected `Hassan Ali` because he covers that area
   - Save the order.

8. **Walk the order through to Delivered** on the Edit page:
   - Click "Confirm order" → "Start preparing" → "Out for delivery" → "Mark delivered"

9. **Check the commission was generated** → Ambassadors → Commissions:
   - One row: `Hassan Ali`, your order #, base = subtotal − discount, percent `10%`, amount = 10% of base, status **Pending**.
   - Nav badge for Commissions shows `1` (yellow).

10. **Pay the commission** → click **"Mark paid"** action:
    - Pick payment method (Cash / Bank Transfer / JazzCash / Other), optional note. Confirm.
    - Status flips to **Paid** with timestamp + paid-by user.

11. **Bulk payout test** — create a few more orders for the same ambassador, mark them all delivered, then on the Commissions list:
    - Switch to "Pending" tab → tick all rows
    - Click "Mark selected as paid" → pick method → confirm → all flip to Paid in one go.

12. **Ambassador performance** → Ambassadors list now shows for Hassan: orders delivered count, pending payout (Rs 0 after step 10), paid out (Rs amount).

---

## Edge cases handled

- Cancelling a delivered order auto-cancels its pending commission
- Deleting a stock movement reverses its effect on the balance (uses model `deleted` event)
- Stock balance is capped at 0 — releasing more than available won't go negative
- Commission is unique per (ambassador, order) — re-delivering won't double up

---

## Next: Phase 6 — Customer Management + Reporting

Per your spec's "do after ambassador, vendor" note. 4–5 days:
- Customer 360 page (order history, AOV, favorites, smart alerts)
- CSV import for legacy customers
- Customer segmentation badges (Active/Warm/Inactive/Lost)
- Re-engagement lists (no order in 7/14/30 days)
- Sales / Top SKUs / New vs Returning / Retention reports
- Ambassador & Vendor performance reports
- CSV export everywhere

Reply with **"phase 6"** when you're ready.
