# 🛒 Grocery Shop Management System

A full-stack grocery delivery platform built with **Laravel 11** and **Filament v3**.
Designed for a real-world apartment-delivery operation — featuring a customer-facing
storefront, a complete admin control panel, a brand-ambassador micro-warehouse model,
vendor & purchase tracking, customer segmentation, and CSV-driven reporting.

> Built phase by phase from a real product spec — every module is wired into the same
> data layer, so every customer order, every commission, and every CSV export reads
> from one source of truth.

---

## ✨ Features

### 🌐 Customer Storefront (`/`)
- Bootstrap 5 + Font Awesome UI with a green-themed, mobile-first design
- Home page with hero, search, category tabs, and product grid
- Product detail page with image gallery, "items included" list, and qty stepper
- Session-based AJAX cart with live count, line-total updates, and toast notifications
- Coupon application with real-time validation
- Checkout with area-based delivery charges, live total calculation, and live geolocation capture
- Order success page with status timeline (Pending → Delivered)
- Phone-based order tracking

### 🎛️ Admin Panel — Filament v3 (`/admin`)
Custom-branded panel (no "Filament" branding) with role-based access (Admin / Staff / Ambassador / Vendor).

#### Catalog
- **Products**: auto-generated category-prefixed SKUs (e.g. `PRC-0001`, `FRZ-0001`),
  cover image + gallery, stock quantity, low-stock badge, "items included" repeater
- **Categories**: CRUD with image, sort order, and active toggle

#### Sales
- **Orders**: 6-state status workflow (Pending → Confirmed → Preparing →
  Out for Delivery → Delivered → Cancelled), full audit trail of status changes,
  manual order creation with phone-search customer auto-create, items repeater,
  area-based delivery charge, coupon application with live preview,
  WhatsApp-ready "Copy text" action with one-click clipboard
- **Coupons**: percent or flat, min order, max cap, usage limits, expiry window,
  one-click random code generator

#### People
- **Customers**: phone-keyed unique records with rolling stats (total orders,
  total spend, AOV, last order, favorite product), customer segmentation
  (🟢 Active / 🟡 Warm / 🔴 Inactive / ⚫ Lost / 🆕 New), VIP filter,
  CSV import/export, full order history per customer

#### Ambassadors (Micro-Warehouse Model)
- **Ambassadors**: linked to area + building + commission plan
- **Commission Plans**: configurable % per plan
- **Stock Items**: warehouse items (kofta, kabab, etc.) distinct from products
- **Assign / Release stock** actions with live balance tracking
- **Stock Movements**: full audit trail
- **Auto-assign** orders to ambassadors by area
- **Commissions**: auto-generated on order delivery, single + bulk payout actions

#### Operations
- **Vendors**: CRUD with contact, address, supply categories
- **Purchases**: line items with cost prices, invoice photo upload,
  payment status (Paid / Unpaid / Partial) auto-derived from `paid_amount` vs `total`
- **Areas**: delivery zones with per-area charges

#### Reports
- Sales overview (today/week/month + revenue + AOV + customer count)
- Customer retention metrics (segment counts + repeat rate %)
- Daily orders chart (last 30 days, dual-axis line)
- New vs Returning customers (doughnut)
- Top Products & Top Customers tables
- CSV export from every list view (customers, orders, products)

---

## 🛠️ Tech Stack

| Layer | Technology |
|---|---|
| Framework | Laravel 11.x |
| PHP | 8.2+ |
| Admin Panel | Filament v3.3 |
| Permissions | Spatie Laravel Permission v6 |
| Database | MySQL 8 |
| Storefront UI | Blade + Bootstrap 5 + Font Awesome 6 + vanilla JS |
| Charts | Chart.js (via Filament Chart Widgets) |
| Local stack | Laragon / Laravel Sail compatible |

---

## 🚀 Installation

### Requirements
- PHP **8.2+**
- Composer 2
- MySQL 8 (or MariaDB 10.4+)
- Node.js 18+ (only for asset compilation if customising Filament theme)

### Setup

```bash
# Clone the repo
git clone https://github.com/<your-username>/grocery-shop.git
cd grocery-shop

# Install dependencies
composer install

# Copy the env file and generate app key
cp .env.example .env
php artisan key:generate

# Configure your DB credentials in .env, then:
php artisan migrate --seed

# Storage symlink for image uploads
php artisan storage:link

# Publish Filament assets
php artisan filament:assets

# Serve
php artisan serve
```

### Default super-admin
After seeding you can log in at `/admin/login`:

- **Email:** `admin@groceryshop.test`
- **Password:** `password`

> Change the password from the user menu after first login.

### Customer storefront
The customer site is at the root URL: `http://127.0.0.1:8000/`.

---

## 📂 Project Structure (high level)
