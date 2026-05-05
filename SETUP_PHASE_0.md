# Phase 0 — Setup Instructions

Run the following commands in order from the project root (`E:\laragon\www\GroceryShop`) using the **Laragon terminal** (or a terminal where PHP + Composer are on PATH).

---

## 1. Create the database

Open Laragon → HeidiSQL (or phpMyAdmin) and create a new MySQL database:

- Database name: `grocery_shop`
- Charset: `utf8mb4_unicode_ci`

(`.env` is already configured to point at `127.0.0.1` / root / no password / db `grocery_shop`.)

---

## 2. Install the new packages (Filament v3 + Spatie Permission)

```bash
composer update
```

This will pull in `filament/filament` ^3.2 and `spatie/laravel-permission` ^6.10 which were added to `composer.json`.

---

## 3. Publish & migrate Spatie Permission tables

```bash
php artisan vendor:publish --provider="Spatie\Permission\PermissionServiceProvider"
```

This publishes the permission migration + config.

---

## 4. Run migrations + seed the roles and super-admin

```bash
php artisan migrate:fresh --seed
```

This will:

- Create all base tables (`users`, `sessions`, `cache`, `jobs`, plus Spatie's `roles`/`permissions`/etc.)
- Create the four roles: **Admin, Staff, Ambassador, Vendor**
- Create the default super-admin user

---

## 5. Create the storage symlink (for product/invoice images later)

```bash
php artisan storage:link
```

---

## 6. Optional: pretty URL via Laragon

Restart Laragon → Auto-detects `groceryshop.test` (since the folder is `GroceryShop`).
If you prefer `localhost`, change `APP_URL` in `.env` to `http://localhost/GroceryShop/public`.

---

## 7. Start the dev server

```bash
php artisan serve
```

or just visit `http://groceryshop.test/admin` if Laragon auto-host is on.

---

## 8. Login to the admin panel

URL: `http://groceryshop.test/admin/login`

- **Email:** `admin@groceryshop.test`
- **Password:** `password`

You should land on the Filament dashboard.  
**Important:** change this password from your profile menu after first login.

---

## What was set up

- ✅ `.env` rebranded ("Grocery Shop", `Asia/Karachi`, MySQL, public disk)
- ✅ `composer.json` updated with Filament v3 and Spatie Permission
- ✅ `app/Providers/Filament/AdminPanelProvider.php` — green theme, branded "Grocery Shop Admin", navigation groups pre-defined (Catalog / Sales / People / Operations / Reports / Settings), dark mode + collapsible sidebar
- ✅ Provider registered in `bootstrap/providers.php`
- ✅ `User` model — implements `FilamentUser`, uses `HasRoles`, gates panel access to active Admin/Staff users
- ✅ `users` migration extended with `phone` and `is_active`
- ✅ `RolesAndAdminSeeder` — creates 4 roles + super admin
- ✅ Empty `app/Filament/{Resources,Pages,Widgets}` directories ready for Phase 1

---

## Verifying Phase 0 is complete

After running the commands above, you should be able to:

1. Visit `/admin/login` and see the Grocery Shop Admin login screen (green theme)
2. Log in as `admin@groceryshop.test` / `password`
3. See an empty dashboard with the navigation groups ready (sidebar will be empty until Phase 1 adds resources)
4. Log out and back in successfully

If all of that works → **Phase 0 done.** Tell me and I'll start **Phase 1: Product Management** (Categories + Products + Product Items + image uploads).
