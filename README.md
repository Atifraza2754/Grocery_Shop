<<<<<<< HEAD
<p align="center"><a href="https://laravel.com" target="_blank"><img src="https://raw.githubusercontent.com/laravel/art/master/logo-lockup/5%20SVG/2%20CMYK/1%20Full%20Color/laravel-logolockup-cmyk-red.svg" width="400" alt="Laravel Logo"></a></p>

<p align="center">
<a href="https://github.com/laravel/framework/actions"><img src="https://github.com/laravel/framework/workflows/tests/badge.svg" alt="Build Status"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/dt/laravel/framework" alt="Total Downloads"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/v/laravel/framework" alt="Latest Stable Version"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/l/laravel/framework" alt="License"></a>
</p>

## About Laravel

Laravel is a web application framework with expressive, elegant syntax. We believe development must be an enjoyable and creative experience to be truly fulfilling. Laravel takes the pain out of development by easing common tasks used in many web projects, such as:

- [Simple, fast routing engine](https://laravel.com/docs/routing).
- [Powerful dependency injection container](https://laravel.com/docs/container).
- Multiple back-ends for [session](https://laravel.com/docs/session) and [cache](https://laravel.com/docs/cache) storage.
- Expressive, intuitive [database ORM](https://laravel.com/docs/eloquent).
- Database agnostic [schema migrations](https://laravel.com/docs/migrations).
- [Robust background job processing](https://laravel.com/docs/queues).
- [Real-time event broadcasting](https://laravel.com/docs/broadcasting).

Laravel is accessible, powerful, and provides tools required for large, robust applications.

## Learning Laravel

Laravel has the most extensive and thorough [documentation](https://laravel.com/docs) and video tutorial library of all modern web application frameworks, making it a breeze to get started with the framework.

You may also try the [Laravel Bootcamp](https://bootcamp.laravel.com), where you will be guided through building a modern Laravel application from scratch.

If you don't feel like reading, [Laracasts](https://laracasts.com) can help. Laracasts contains thousands of video tutorials on a range of topics including Laravel, modern PHP, unit testing, and JavaScript. Boost your skills by digging into our comprehensive video library.

## Laravel Sponsors

We would like to extend our thanks to the following sponsors for funding Laravel development. If you are interested in becoming a sponsor, please visit the [Laravel Partners program](https://partners.laravel.com).

### Premium Partners

- **[Vehikl](https://vehikl.com/)**
- **[Tighten Co.](https://tighten.co)**
- **[WebReinvent](https://webreinvent.com/)**
- **[Kirschbaum Development Group](https://kirschbaumdevelopment.com)**
- **[64 Robots](https://64robots.com)**
- **[Curotec](https://www.curotec.com/services/technologies/laravel/)**
- **[Cyber-Duck](https://cyber-duck.co.uk)**
- **[DevSquad](https://devsquad.com/hire-laravel-developers)**
- **[Jump24](https://jump24.co.uk)**
- **[Redberry](https://redberry.international/laravel/)**
- **[Active Logic](https://activelogic.com)**
- **[byte5](https://byte5.de)**
- **[OP.GG](https://op.gg)**

## Contributing

Thank you for considering contributing to the Laravel framework! The contribution guide can be found in the [Laravel documentation](https://laravel.com/docs/contributions).

## Code of Conduct

In order to ensure that the Laravel community is welcoming to all, please review and abide by the [Code of Conduct](https://laravel.com/docs/contributions#code-of-conduct).

## Security Vulnerabilities

If you discover a security vulnerability within Laravel, please send an e-mail to Taylor Otwell via [taylor@laravel.com](mailto:taylor@laravel.com). All security vulnerabilities will be promptly addressed.

## License

The Laravel framework is open-sourced software licensed under the [MIT license](https://opensource.org/licenses/MIT).
=======
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
>>>>>>> 54663a483ae5740ab1be4b65ab314e4755ce99e9
