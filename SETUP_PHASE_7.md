# Phase 7 — Admin Dashboard

The home screen at `/admin`. Built per spec — required items only, "in advance" charts skipped.

---

## What's been built

### 3 widgets at `/admin`

All in `app/Filament/Widgets/` — auto-discovered by the panel and shown on the default Dashboard page.

| Widget | What it shows |
|---|---|
| **DashboardStats** (sort 1) | 4 stat tiles: Orders today, Revenue today, Pending orders, Active customers (#) |
| **DashboardPendingOrders** (sort 2) | Up to 10 pending orders with quick **Confirm** action + Open link |
| **DashboardTopProducts** (sort 3) | Top 5 SKUs by qty in the last 7 days, with revenue + orders count |

### Stat-tile details

| Tile | Source | Click action |
|---|---|---|
| **Orders today** | `Order::where('created_at','>=',today)` minus cancelled | → All orders |
| **Revenue today** | sum of `total` for today's non-cancelled orders | (no click) |
| **Pending orders** | count where `status = pending` | → Orders filtered to Pending |
| **Active customers** | count where `total_orders > 0` AND `last_order_at >= 7 days` | → All customers |

The Pending tile turns **yellow with a warning icon** when there's anything waiting; gray with a check when caught up.

### Pending-orders queue

Shows the 10 most recent pending orders with:
- Order #, customer name + phone, area, total, pricing badge ("Needs pricing" if has unpriced grocery items), placed-since
- Inline **Confirm** action (one-click confirm with confirmation modal)
- **Open** action that takes you to the full edit page

### Top products (last 7 days)

Top 5 SKUs ordered by quantity sold. Excludes cancelled orders and unpriced grocery requests. Shows: SKU badge, name, sold qty (green badge), unique orders count, revenue.

---

## Run these commands

```bash
php artisan optimize:clear
```

That's it — no migrations, no seeders, no extra packages. The widgets are pure PHP/Eloquent over the data you already have.

---

## Verify

1. Hard-refresh `/admin`. You should now see (top to bottom):
   - **Welcome** widget (existing AccountWidget)
   - **4 stat tiles** in one row
   - **Pending orders** table (or "All orders confirmed" empty state)
   - **Top products** table (or empty if no orders in last 7 days)

2. **Stat tiles**:
   - Place a new test order from the customer site → refresh dashboard → "Orders today" goes up by 1, "Revenue today" goes up by the order total
   - The order's status is **Pending** → "Pending orders" tile becomes yellow with warning icon, count = 1
   - Click "Pending orders" tile → you should land on `/admin/orders` with the Pending status filter applied
   - Click "Active customers" tile → you should land on `/admin/customers`

3. **Pending orders queue**:
   - Your test order shows in the table
   - Click the **Confirm** action → confirmation modal → click "Confirm" → notification appears, order disappears from this list (it's now Confirmed)
   - Click the **Open** action on another pending order → takes you to its edit page

4. **Top products**:
   - As you place + deliver more orders with regular products in the last 7 days, top SKUs populate here

---

## What's NOT here ("in advance" — skipped per your spec)

- Sales graph (daily/weekly bar chart)
- Orders trend (time-series chart)

These advanced visualisations are explicitly marked "in advance" in your doc. The data is already aggregated and exposed via the Reports page (`/admin/reports`) for the more detailed views.

---

## Next ideas

If you want to extend the dashboard later, easy adds:

- **Out-for-delivery widget** — real-time queue of orders en route (similar to pending)
- **Low stock products** — products where `stock_qty <= low_stock_threshold`
- **VIP customer alerts** — first-time / inactive 7d / spending Rs 10k+ badges
- **Today's commissions** — for ambassador payouts due

Tell me which one and I'll add it.
