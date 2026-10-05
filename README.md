# Lily Crown

Online clothing store for Bangladesh (sarees, three-piece, kurtis, lehengas) with Cash on Delivery.

- `backend_lily_crown/` — Laravel 12 API + Backpack admin panel (SQLite by default)
- `frontend_lily_crown/` — Next.js 16 storefront

## Features

- Catalogue with categories, search, price filter, sale prices, sizes with per-size stock, colours
- Cart, checkout with coupons and delivery charges (inside / outside Dhaka, free-delivery threshold)
- Prices, stock and coupons are always calculated on the server; cancelling an order restocks it
- Customer accounts (register / login with mobile number), order history, order tracking
- Admin panel: dashboard, orders (one-click status updates, printable invoice), products, coupons,
  customers, homepage banners, lookbook, testimonials, blog and store settings

## Running locally

Requirements: PHP 8.2+ (with `pdo_sqlite`), Composer, Node.js 20+.

```bash
# Backend — http://localhost:8000
cd backend_lily_crown
composer install
cp .env.example .env
php artisan key:generate
touch database/database.sqlite
php artisan migrate
php artisan db:seed --class=AdminSeeder
php artisan db:seed --class=StoreSeeder   # demo catalogue + photos (downloads from Pexels)
php artisan storage:link
php artisan serve --port=8000
```

```bash
# Frontend — http://localhost:3000
cd frontend_lily_crown
npm install
npx next dev --webpack
```

Set `NEXT_PUBLIC_API_URL` (default `http://localhost:8000/api`) if the API runs elsewhere.

Admin panel: http://localhost:8000/admin — the seeded admin is `admin@lilycrown.com` / `password`.
**Change this password before going live**, and set `APP_DEBUG=false` in production.

## Tests

```bash
cd backend_lily_crown && php artisan test
```
