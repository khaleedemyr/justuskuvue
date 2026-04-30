# Staging and Cutover Checklist (Phase 1)

## 1. Environment
- Set `YMSOFTERP_API_URL` to reachable ERP API base (must include `/api`).
- Ensure `APP_URL` matches staging domain.
- Run:
  - `composer install`
  - `npm install`
  - `php artisan migrate --force`
  - `npm run build`

## 2. Route parity checks
- Home: `/`
- Brands: `/brands`
- Careers: `/careers`
- Careers scopes: `/careers/head-office`, `/careers/outlet`
- News list/detail: `/whats-on`, `/news/{id}`
- Justus Apps: `/justus-apps`

## 3. Data parity checks against old Next.js
- Navbar labels and order.
- Home hero/banner and home blocks.
- Brand logos and cards.
- Careers page wording, cards, and scope lists.
- What's On cards and detail rendering.
- Justus Apps hero + blocks.

## 4. Failure behavior checks
- If ERP API is down, pages still render gracefully (empty states).
- No 500 error due to HTTP connection failure.

## 5. Production cutover
- Freeze new feature changes in old Next app.
- Deploy Laravel app to staging path and run parity checklist.
- Switch web root to Laravel app after validation.
- Monitor first 24 hours for endpoint latency and broken media URLs.

