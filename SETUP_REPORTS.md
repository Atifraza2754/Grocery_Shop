# Reports Phase — Setup

Adds 7 new report widgets + 1 schema change to your existing Reports page (`/admin/reports`). All additive — old widgets and CSV exports still work.

---

## Run these commands

```bash
php artisan migrate
php artisan optimize:clear
```

The migration adds `cost_price` (decimal, default 0) to the `products` table. Existing products get `cost_price = 0`, which means they won't appear in the High-margin report until you fill in real cost prices.

---

## What's new

### Schema
- `products.cost_price` — decimal with default 0. Form field added in **Catalog → Products → Pricing & stock** section.

### Sales report widgets
1. **Peak order time chart** — bar chart of orders by hour of day (00 – 23) for the last 30 days. Shows when your customers actually order.
2. **Revenue by area table** — orders count, total revenue, AOV, and delivery rate per area. Sorted by revenue descending.

### Product report widgets
3. **Pending products table** — roll-up of products in pending/confirmed/preparing orders with total qty needed across all those orders. **"Copy list"** action produces a clean WhatsApp-ready text block of what to prepare.
4. **Slow movers table** — products that haven't been ordered in the selected window. Filter buttons for **7 / 14 / 30 days**. Defaults to 14.
5. **High margin table** — top 15 products by margin amount (price − cost_price). Shows margin Rs and margin %, color-coded (≥30% green, ≥15% yellow, less = gray). Empty state explains how to fill cost prices.

### Customer report widget
6. **Inactive customers table** — customers with last order older than the selected window. Filter buttons for **7+ / 14+ / 30+ / 60+ days**. Shows orders count, total spend, favorite product, and segment badge.

### Vendor report widget
7. **Vendor cost trends table** — last 6 months of purchases per vendor with: total spent, paid, balance due (red if > 0), and a per-month breakdown string ("Jan: Rs 5,000 · Feb: Rs 3,200 · …").

---

## Spec checklist

| Spec line | Status | Where |
|---|---|---|
| 📈 **Sales** — Daily/Monthly revenue | ✅ | Sales overview stats |
| Revenue by area | ✅ | RevenueByAreaTable |
| Revenue by product | ✅ | TopProductsTable (revenue column) |
| Most frequent order time | ✅ | PeakOrderTimeChart |
| 📦 **Product** — Top SKUs by qty / sale | ✅ | TopProductsTable |
| Pending order product list with copy | ✅ | PendingProductsTable + Copy list action |
| Not ordered in last X days | ✅ | SlowMoversTable (7/14/30 filters) |
| High margin product list | ✅ | HighMarginTable (set cost_price first) |
| 👥 **Customer** — Repeat vs new (with details) | ✅ | NewVsReturningChart + segment badges + favorite product |
| Inactive 7-14 days filter | ✅ | InactiveCustomersTable (7/14/30/60 filters) |
| 🏠 **Ambassador** — Orders handled | ✅ already done | Ambassadors list |
| Revenue generated | ✅ already done | Ambassadors list |
| 🏭 **Vendor** — Total spending | ✅ already done | Vendors list |
| Cost trends | ✅ | VendorCostTrendsTable |

---

## Verify

1. Hard-refresh `/admin/reports`. Widget order from top to bottom:

   1. Sales overview stats (orders today/week/month + AOV + customer counts)
   2. Customer retention stats (Active/Warm/Inactive/Lost + Repeat rate)
   3. Daily orders chart
   4. **Peak order time chart** (NEW — bar chart by hour)
   5. New vs Returning doughnut
   6. **Revenue by area** (NEW)
   7. Top products (top 10 by qty)
   8. **Pending products** (NEW — with Copy list)
   9. **Slow movers** (NEW — with 7/14/30 filters)
   10. **High margin** (NEW — needs cost prices)
   11. Top customers
   12. **Inactive customers** (NEW — with 7/14/30/60 filters)
   13. **Vendor cost trends** (NEW)

2. **Set a cost price** on at least one product:
   - `/admin/products` → edit any product → fill **Cost price** (e.g. 80 if price is 120) → Save
   - Refresh `/admin/reports` → product appears in **High margin** with Margin = Rs 40, Margin % = 33.3% (green)

3. **Pending products copy**:
   - Place a few customer orders to create pending orders
   - Reports → Pending products → click **Copy list** → modal opens with a formatted text block → click "Copy to clipboard" → paste anywhere

4. **Slow movers / Inactive customers filters** — click the day-window buttons (7/14/30 days etc) → page refreshes with new query params, table re-queries with the new window.

5. **Vendor cost trends** — populate after creating some Phase 4 purchases. Each vendor row shows a 6-month spending breakdown.

---

## CSV downloads (already in place from Phase 6)

- `/admin/customers` → **Export CSV** → all customers with stats
- `/admin/orders` → **Export CSV** → all orders
- `/admin/products` → **Export CSV** → all products

These cover the underlying data for the report widgets.

---

## What's NOT in this phase

These were either marked "in advance" earlier or fall under a future phase:

- AI / chat-prompt report filters
- Per-widget inline CSV download (current pattern: visualise on Reports page → download from list pages)
- Predictive analytics (forecasting) — the Reports page is descriptive, not predictive

---

## Next ideas (easy wins)

- **Frequently bought together** — market basket analysis on order_items
- **DAU / MAU** — needs visit tracking middleware (currently we only track orders)
- **Low-margin alerts** — flag products with margin < threshold
- **Re-engagement broadcast** — send WhatsApp templates to inactive customers in bulk

Tell me which one you want next.
