# Phase 6 — Customer Management + Reporting

Customer 360, smart insights, segmentation, CSV import/export, and a Reports page with charts.

---

## What's been built

### Customer model — new accessors

- `segment` — `active` (≤7d) / `warm` (7–30d) / `inactive` (30–60d) / `lost` (60d+) / `new` (no orders)
- `segment_label`, `segment_color` — for UI badges
- `favorite_product` — most-ordered product name from non-cancelled orders
- `isVip($threshold)` / `isFirstTime()` / `inactiveDays()` helpers

### CustomerResource (overhauled)

- **List columns**: name + phone tooltip, **segment badge** (🟢🟡🔴⚫🆕), area, orders count, total spend, AOV, favorite product, last order, active toggle
- **Filters**:
  - Area
  - **Segment** — Active / Warm / Inactive / Lost / New
  - **VIP** — Rs 10,000+
  - Active toggle, trashed
- **Edit page**:
  - Form section with editable fields
  - Read-only **Stats panel** — total orders, total spend, AOV, segment, last order, favorite product
  - **Order history** relation manager — every order with status badge + payment badge + total
- **Header actions on list page**:
  - **Import CSV** — modal upload, parses headers (`phone, name, email, address, area, notes`), upserts by phone, looks up area by name, shows summary "Created X / Updated Y / Skipped Z"
  - **Export CSV** — downloads all customers with stats columns

### CSV exports added to other lists

- **Orders** → "Export CSV" header action — `order_no, placed_at, customer_*, area, ambassador, subtotal, discount, delivery_charge, total, status, payment_*, coupon_code`
- **Products** → "Export CSV" — `sku, name, category, unit, price, compare_price, stock_qty, is_active, is_featured`

### Reports page (Reports → Reports)

Custom Filament page with 6 widgets:

1. **Sales Overview Stats** — Orders today / week / month (each with revenue), AOV month-to-date, Total customers (and VIP count)
2. **Customer Retention Stats** — Active / Warm / Inactive / Lost counts + **Repeat rate %**
3. **Daily Orders Chart** — line chart of last 30 days (orders count + revenue, dual y-axis)
4. **New vs Returning Doughnut** — split of customers in last 30 days
5. **Top Products Table** — top 10 by qty sold (last 30 days), with revenue + orders count
6. **Top Customers Table** — top 10 by total spend, with segment badges

Widgets are placed in `app/Filament/Pages/Reports/Widgets/` (outside the auto-discovery path) so they only appear on the Reports page, not the dashboard.

---

## Run these commands

```bash
php artisan optimize:clear
```

No migrations or seeders this phase — purely additive UI / model code.

---

## Verify

1. Hard-refresh `/admin`. Sidebar shows new **Reports** group → **Reports** page.

2. **Reports page**:
   - Top row: 5 stat cards (orders today/week/month, AOV, total customers)
   - Second row: 5 customer-segment stat cards
   - Below: line chart (orders + revenue last 30 days), doughnut (new vs returning)
   - Below: Top products + Top customers tables

3. **Customers list**:
   - You should see the segment column with colored badges
   - Try the **Segment** filter → "🟢 Active (last 7d)" etc.
   - Click **Import CSV** → upload `storage/app/sample_customers.csv` (already created for you) → modal shows "Created 4 / Updated 0 / Skipped 0"
   - List now has 4 new customers
   - Click **Export CSV** → downloads `customers_YYYYMMDD_HHMMSS.csv`

4. **Customer edit page** (click Edit on any customer):
   - Form section
   - Stats panel: total orders, total spend, AOV, segment, last order, favorite product
   - **Order history** below the form — full list of that customer's orders with status badges

5. **Orders list** → **Export CSV** → downloads orders CSV

6. **Products list** → **Export CSV** → downloads products CSV

---

## Sample CSV format (Customer Import)

File: `storage/app/sample_customers.csv` (already created)

```
phone,name,email,address,area,notes
03001111111,Ali Khan,ali@example.com,House 12 Street 5,DHA Phase 5,Regular customer
03002222222,Sara Ahmed,sara@example.com,Apt 4B Marina Tower,DHA Phase 5,Likes spicy food
03003333333,Bilal Hassan,,Block 9,Gulshan-e-Iqbal,
03004444444,Fatima Noor,fatima@example.com,House 21,Bahadurabad,VIP - large orders
```

**Header rules**:
- Required: `phone`, `name`
- Optional: `email`, `address`, `area`, `notes`
- `area` must match an existing Area name (case-insensitive) — otherwise area_id stays null
- Headers can be in any order; lowercase
- Rows missing `phone` or `name` are skipped

---

## Spec checklist

| Spec line | Done? | Where |
|---|---|---|
| Name + Phone (primary key) | ✅ | `customers.phone` unique constraint |
| Address | ✅ | form + import |
| Order history | ✅ | OrdersRelationManager on edit page |
| Total orders count | ✅ | column + read-only stat panel |
| Total spend | ✅ | column + stat panel |
| **Upload previous customer data (CSV)** | ✅ | Import action with summary |
| Repeat vs new customer | ✅ | Reports → New vs Returning chart + Repeat rate stat |
| Most ordered products | ✅ | Reports → Top Products table |
| Average order value | ✅ | column + Reports stat |
| Top customers (VIP) | ✅ | Reports → Top Customers + VIP filter |
| Daily / Weekly / Monthly orders | ✅ | Reports → Sales Overview Stats |
| Revenue tracking | ✅ | every stat + chart |
| Product performance (SKU wise) | ✅ | Reports → Top Products table |
| Customer retention % | ✅ | Reports → Repeat rate stat |

Plus customer segmentation:

| Segment | Done? |
|---|---|
| 🟢 Active (last 7 days) | ✅ |
| 🟡 Warm (7–30 days) | ✅ |
| 🔴 Inactive (30–60 days) | ✅ |
| ⚫ Lost (60+ days) | ✅ |
| 🆕 New (no orders) | ✅ |

---

## What's NOT in Phase 6 (per "in advance" rule)

- AI chat-prompt report filters → Phase 9
- Abandoned cart tracking → Phase 9 (needs customer-facing site)
- Custom date range picker on Reports page → can add easily later

---

## Next: Phase 7 — Admin Dashboard

The home/landing screen at `/admin`. Will reuse the widgets we built here:

- Sales overview tile row
- Pending orders list
- Today's revenue, AOV
- Sales graph ("in advance" version skipped per your rule, but the basic line chart is fine)
- Quick links

Reply with **"phase 7"** when ready.
