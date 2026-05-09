# Brand Ambassador System v2 — Setup

Adds the price-based commission flow without breaking the existing Phase 5 ambassador work. All changes are additive — old order-based commission code path still works for backwards compat.

---

## Run these commands

```bash
php artisan migrate
php artisan optimize:clear
```

Two migrations run:

1. `add_price_to_stock_items` — adds `price` column to `stock_items` (default 0)
2. `link_commissions_to_stock_movements` — makes `order_id` nullable on `commissions`, adds `stock_movement_id`, `plan_id`, and `paid_amount` columns

Existing commissions get `paid_amount = 0` and keep their old status. Existing stock items get `price = 0` (you'll need to fill these in).

---

## What's new

### 1. Stock items now have a price
- `/admin/ambassadors → Stock Items`
- Form has a new **Price (per unit)** field
- List shows the price column
- Used for release-based commission calculation

### 2. Commission flow on stock release

When admin releases stock from an ambassador, a commission is **automatically created**:

```
commission_amount = release_qty × stock_item.price × selected_plan.percent / 100
```

Example from your spec:
- Assigned 2 dozen kofta @ Rs 1000 = Rs 2000 worth
- Released 2 dozen → with Silver (10%) plan
- Commission = 2 × 1000 × 10% = **Rs 200** (saved as a Commission row)

### 3. Plan picker on the Release modal
- The release form now has a **Commission plan** select
- **Defaults to the ambassador's plan** (their assigned plan)
- Admin can override per release if needed
- Selected plan is snapshotted on the commission, so changes to the plan later don't affect history
- A live "Commission preview" card shows the math before submit:
  > Base: Rs 2,000 (2 dozen × Rs 1,000)
  > Plan: Silver (10%)
  > **Commission: Rs 200**

### 4. Partial payouts

Each commission row now has:
- `amount` — total earned
- `paid_amount` — running total of payments made
- `remaining` (computed) — `amount − paid_amount`
- `status` — Pending → Partial → Paid (or Cancelled)

#### Pay action — three places

**A. On the Commission list (per row)**
- "Pay (Rs X)" button shows the remaining amount
- Click → modal pre-filled with full remaining
- Edit the amount down for partial; submit
- Status auto-flips: 0 paid = Pending, partial paid = Partial, fully paid = Paid

**B. On the Commission edit page**
- `paid_amount` is now an editable field for manual fixes if you ever need to correct a payment record

**C. On Ambassador edit page header** ("Pay out" action)
- Shows total remaining across all that ambassador's commissions
- Enter amount → distributes to **oldest pending commissions first**
- Returns: "Paid Rs X. Remaining: Rs Y"

**D. On Ambassador list (per row)**
- "Pay XXX" action shows the ambassador's outstanding balance
- Same partial-pay flow as above

### 5. New tabs on Commissions list
- **All** | **Owing** (badge) | **Pending** | **Partial** | **Paid** | **Cancelled**

Owing badge in the sidebar nav now counts every commission with a remaining balance > 0.

### 6. New columns on Commissions list
- **Paid** — running total paid
- **Remaining** — open balance (yellow if > 0)
- **Source** — "Stock release" or "Order" badge so you can tell where the commission came from

---

## Backwards compatibility

The order-based auto-commission flow still works:
- When an order is delivered, if it has an `ambassador_id` and the ambassador has a plan, a commission is generated based on `subtotal − discount` (old behavior, untouched).

You can use either model:
- **Stock-release model** (recommended per your spec) — assign stock to ambassador, release when sold, commission auto-generated
- **Order model** — assign orders to ambassadors via area, commission on delivery
- Or both

---

## End-to-end verification

1. **Set prices on stock items**
   - `/admin/ambassadors → Stock Items` → edit `Frozen Kofta - Chicken` → set Price = `1000` → Save

2. **Make sure you have a plan**
   - `/admin/ambassadors → Commission Plans` → ensure `Standard 10%` exists, set on your test ambassador

3. **Assign stock to ambassador**
   - Edit ambassador → click **Assign stock** → pick `Frozen Kofta - Chicken`, qty `2` (dozen) → Save
   - Note: assignment does NOT create commission (it's just inventory)

4. **Release stock**
   - Click **Release stock** → pick the same kofta, qty `2`
   - Plan select shows `Standard 10%` selected by default (their plan)
   - **Commission preview** shows: Base Rs 2,000 · 10% = **Rs 200**
   - Save → notification: "Stock released + commission Rs 200 added"

5. **Verify commission**
   - `/admin/ambassadors → Commissions`
   - New row: ambassador name, **Source: Stock release**, base `2000`, percent `10%`, amount `200`, paid `0`, remaining `200`, status **Pending**

6. **Partial payment**
   - Click **Pay (Rs 200)** → modal pre-fills `200` → change to `100` → Save
   - Notification: "Paid Rs 100. Remaining on this commission: Rs 100"
   - Status flips to **Partial**, paid `100`, remaining `100`

7. **Pay the rest**
   - Click **Pay (Rs 100)** → defaults to `100` → Save
   - Status flips to **Paid**, paid_at timestamp recorded

8. **Bulk ambassador-level pay**
   - Create 3 more pending commissions on the same ambassador (e.g., release more stock)
   - On Ambassadors list → click "Pay XXX" → enter `300` → Save
   - System distributes Rs 300 across the oldest commissions first; partials remain on whichever wasn't fully covered

---

## Spec checklist

| Spec line | Done? | Where |
|---|---|---|
| Stock list — add price | ✅ | StockItem form + list |
| Commission = qty × price × plan% | ✅ | Auto on release |
| Plan select on release with default | ✅ | Release modal |
| Override plan per release | ✅ | Plan select stays editable |
| Commission saved based on selected plan | ✅ | Plan snapshotted on commission row |
| Total commission accumulates | ✅ | Multiple releases stack as separate rows |
| Pay button on ambassador list | ✅ | "Pay XXX" action |
| Pay button on commission tab | ✅ | "Pay (Rs X)" per row |
| Shows total remaining | ✅ | In modal heading + pre-filled input |
| Partial input (e.g. 200 owed, pay 100) | ✅ | Editable amount in modal |
| Paid 100 / remaining 100 tracking | ✅ | `paid_amount` + `remaining` columns |
| Multiple partial payments | ✅ | Status flows Pending → Partial → Paid |

---

## Files changed

| File | Change |
|---|---|
| `database/migrations/2026_05_03_000002_add_price_to_stock_items.php` | NEW |
| `database/migrations/2026_05_03_000003_link_commissions_to_stock_movements.php` | NEW |
| `app/Models/StockItem.php` | + price field |
| `app/Models/Commission.php` | + stock_movement, plan, paid_amount, remaining, payAmount(), applyAmbassadorPayment() |
| `app/Models/Ambassador.php` | commission_paid / commission_pending now use paid_amount |
| `app/Filament/Resources/StockItemResource.php` | + price form field + column |
| `app/Filament/Resources/AmbassadorResource.php` | + Pay action on list |
| `app/Filament/Resources/AmbassadorResource/Pages/EditAmbassador.php` | Release form: plan select + commission preview + auto-commission. Plus header-level Pay out action. |
| `app/Filament/Resources/CommissionResource.php` | + Pay action with partial input + paid_amount/remaining/source columns |
| `app/Filament/Resources/CommissionResource/Pages/ListCommissions.php` | + Owing/Partial tabs |

No existing files removed — pure additive changes.

---

## Note on queue worker

If you want WhatsApp delivery notifications to ambassadors when commissions are paid (Phase 8 territory), let me know. For now the partial-payout flow is fully synchronous — the moment you click Pay, the row updates.
