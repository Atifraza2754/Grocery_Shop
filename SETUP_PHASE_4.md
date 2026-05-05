# Phase 4 — Vendor & Inventory Management Setup

Vendors, purchases, line-item costs, invoice photos, payment tracking.

---

## What's been built

### Database (3 new tables)

| Table | Purpose |
|---|---|
| `vendors` | Vendor directory — name, contact person, phone/email, address, what they supply |
| `purchases` | Purchase entries — auto `purchase_no`, vendor, date, totals, payment status, invoice image |
| `purchase_items` | Line items — item name, qty, unit, cost price, line total |

All three have soft deletes.

### Models

- **`Vendor`** — `hasMany` purchases, computed `total_spent` and `outstanding_balance` accessors
- **`Purchase`** — auto-generates `purchase_no` (`PUR-YYYYMMDD-NNNN`), `recalculateTotals()` recomputes subtotal + tax + total + payment_status from items + paid_amount, helpers `paymentStatusLabel()` / `paymentStatusColor()`, `balance_due` accessor
- **`PurchaseItem`** — auto-computes `line_total = cost_price × qty` on save

Payment status auto-determined:
- `paid_amount = 0` → **Unpaid**
- `0 < paid_amount < total` → **Partial**
- `paid_amount >= total` → **Paid**

### Filament Resources

Both under **Operations** group (alongside Areas):

**Operations → Vendors**
- CRUD with name, contact person, phone (copyable), email, address, supplies, notes, active toggle
- Table shows: name + contact, phone, supplies, **purchases count**, **total spent**, **outstanding balance** (red if > 0)

**Operations → Purchases**
- 3-column form:
  - Left (2/3): Vendor + date, items repeater (item name, qty, unit, cost price, live line total), invoice image upload, notes
  - Right (1/3): live **Subtotal**, optional Tax/extra input, live **Total**, **Paid amount** input, live **Balance due** (red if > 0), payment method
- Table shows: purchase #, vendor, date, items count, total, paid, balance, payment status badge, invoice thumbnail
- Filters: by vendor, payment status, this month
- Inline "create new vendor" inside the vendor select
- Auto-recalculates totals + payment status after every save
- **Navigation badge** = count of unpaid + partial purchases (red)

---

## Run these commands

```bash
php artisan migrate
php artisan optimize:clear
```

No seeder for Phase 4 — vendors are user-created.

---

## Verify

1. Hard-refresh `/admin`. New sidebar items under **Operations**:
   - Areas (existing)
   - **Vendors**
   - **Purchases** (with red badge once you have unpaid/partial purchases)

2. **Vendors → New vendor**:
   - Name: `Karachi Sabzi Mandi`, contact: `Ali`, phone: `03331234567`
   - Supplies: `Vegetables, leafy greens`
   - Save → list view shows the vendor with 0 purchases and Rs 0 outstanding.

3. **Purchases → New purchase**:
   - Vendor: pick the one you just created (or use the inline "+" to make a new one)
   - Date: today (default)
   - Add 2 items:
     - `Onion`, qty `10`, unit `kg`, cost `120` → live total = `Rs 1,200`
     - `Tomato`, qty `5`, unit `kg`, cost `200` → live total = `Rs 1,000`
   - Right rail: Subtotal updates to **Rs 2,200** as you type
   - Tax: leave 0 → Total = **Rs 2,200**
   - Paid amount: enter `1,000` → Balance due flips red showing `Rs 1,200`
   - Upload an invoice photo (any image)
   - Save → redirected to edit. Purchase number auto-generated like `PUR-20260502-0001`.

4. Back on the **Purchases list**, you should see the row with payment_status = **Partial** (yellow badge), balance Rs 1,200 (red), and the invoice thumbnail.

5. **Edit the purchase** → change paid amount to `2,200` → save → status flips to **Paid** (green), balance Rs 0 (green).

6. **Vendors list** → that vendor's "Total spent" now shows Rs 2,200 and "Outstanding" shows Rs 0.

7. Filter test:
   - Purchases → filter "Payment status: Unpaid" → empty
   - Filter "This month" → shows your test purchase

---

## What's NOT in Phase 4

- Auto-deduct vendor purchases from product stock (would need the items to map to products) — can be added later when needed
- Multi-payment ledger (recording each payment separately) — Phase 4 uses a single `paid_amount` field; can be extended in a future phase if needed
- Vendor cost-trend report — that comes in Phase 6 (Reporting)

---

## Next: Phase 5 — Brand Ambassador System

The micro-warehouse model — your biggest differentiator. 5–7 days of work:

- Ambassadors (linked to user accounts, area, building, plan)
- Commission plans
- Stock items (kofta, kabab, etc.)
- Assign / release stock to ambassadors
- Stock movement audit trail
- Auto-assign nearby orders to ambassadors
- Commission auto-calculation on delivered orders
- Payout flow
- Performance reports per ambassador

Reply with **"phase 5"** when you're ready. Or **"tweak X"** if you want anything in Phase 4 adjusted first.
