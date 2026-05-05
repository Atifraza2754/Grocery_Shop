# Phase 1 — Product Management Setup

What's been built and how to run it.

---

## What's new in this phase

### Branding polish (pre-Phase-1)

- Removed `FilamentInfoWidget` from the dashboard
- Brand changed from "Grocery Shop Admin" → "Grocery Shop"
- Custom CSS at `public/css/admin.css` — visible sidebar borders, cleaner topbar, branded green active state
- Custom footer ("Grocery Shop · v0.1 · 2026") replaces Filament's

### Database

- `categories` table — `name`, `slug`, `prefix` (unique, e.g. PRC), `description`, `image`, `sort_order`, `is_active`, soft deletes
- `products` table — `category_id`, `sku` (unique, auto-generated), `name`, `slug`, `unit`, `price`, `compare_price`, `stock_qty`, `low_stock_threshold`, `short_description`, `description`, `image`, `is_active`, `is_featured`, `sort_order`, soft deletes
- `product_images` table — gallery images for each product
- `product_items` table — "items included" rows for combo/deal products

### Models

- `App\Models\Category` — `hasMany(Product)`, auto-uppercases prefix, auto-slugs name
- `App\Models\Product` — `belongsTo(Category)`, `hasMany(items, images)`, **auto category-prefixed SKU** on create (e.g. `PRC-0001`, `PRC-0002`...), auto-unique slug, low-stock helpers
- `App\Models\ProductImage`, `App\Models\ProductItem`

### Filament Resources

- **Categories** — under "Catalog" group → image, name, prefix badge, products count, active toggle
- **Products** — under "Catalog" group → cover image + gallery, items repeater, pricing, stock, badges, filters by category / active / featured / low-stock. Navigation badge shows count of low-stock products.

### Seeder — 4 starter categories

| Name | Prefix |
|------|--------|
| Pre-cut Vegetables | `PRC` |
| Frozen Items | `FRZ` |
| Cooking Pastes | `CPT` |
| Pre-cut Deals | `PCD` |

---

## Run these commands

In your Laragon terminal at `E:\laragon\www\GroceryShop`:

```bash
php artisan migrate
php artisan db:seed --class=CategoriesSeeder
php artisan optimize:clear
```

Or to start fresh (wipes products + categories — good for first-time):

```bash
php artisan migrate:fresh --seed
```

---

## Verify it works

1. Hard-refresh `http://127.0.0.1:8000/admin` (Ctrl + F5)
2. **Branding:** sidebar should now have a clear right border, gray-50 background, green active state. Bottom of every page reads "Grocery Shop · v0.1 · 2026". The "filament" widget is gone.
3. **Sidebar:** under the "Catalog" group → **Categories** and **Products**.
4. **Categories →** you should see 4 seeded categories with their prefix badges (PRC, FRZ, CPT, PCD).
5. **Products → Create** → pick a category, fill name + price + stock → save. SKU should auto-fill on the edit screen as `{PREFIX}-0001`.
6. Add a 2nd product in the same category → its SKU should be `{PREFIX}-0002`.

---

## Try the auto-SKU

1. Click **Products → New product**
2. Pick **Pre-cut Vegetables** as category
3. Name: `Onion - Chopped`, Unit: `g`, Price: `200`, Stock: `50`
4. Add 2 items in the "Items included" section:
   - `Onion - chopped`, qty `250`, unit `g`
   - `Mint`, qty `5`, unit `g`
5. Save → you'll see SKU = `PRC-0001` and items saved.
6. Repeat with `Capsicum - Diced` → SKU = `PRC-0002`.
7. Switch to Frozen → first product becomes `FRZ-0001`.

---

## What Phase 1 does NOT include (yet)

These come in later phases:

- Customer-facing storefront — Phase 9
- Cart / checkout — Phase 9
- Order placement — Phase 3
- Coupons — Phase 2

---

## File map (for reference)

```
app/
├── Filament/Resources/
│   ├── CategoryResource.php
│   ├── CategoryResource/Pages/{ListCategories, CreateCategory, EditCategory}.php
│   ├── ProductResource.php
│   └── ProductResource/Pages/{ListProducts, CreateProduct, EditProduct}.php
├── Models/{Category, Product, ProductImage, ProductItem}.php
└── Providers/Filament/AdminPanelProvider.php   (updated: no Filament widget, render hooks)

database/
├── migrations/
│   ├── 2026_05_01_000001_create_categories_table.php
│   ├── 2026_05_01_000002_create_products_table.php
│   ├── 2026_05_01_000003_create_product_images_table.php
│   └── 2026_05_01_000004_create_product_items_table.php
└── seeders/{CategoriesSeeder, DatabaseSeeder, RolesAndAdminSeeder}.php

public/css/admin.css                            (custom Filament overrides)
```

---

## Done? → Next up: Phase 2 — Coupon Management

Reply with "phase 2" or any tweaks you want first.
