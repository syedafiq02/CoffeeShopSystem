# ☕ Nōva Brew — Coffee Shop Management System

A full-stack coffee shop management system built with **native PHP**, **MySQL**, and **Bootstrap 5** — no frameworks. Covers the complete flow from public storefront and customer accounts through cart/checkout with a simulated payment gateway, promo codes, and a full admin panel with printable reports.

## Tech Stack

- **Backend:** Native PHP (PDO, prepared statements, session-based auth)
- **Database:** MySQL
- **Frontend:** HTML5, CSS3 (custom design system), Bootstrap 5, vanilla JavaScript
- **Environment:** XAMPP (Apache + MySQL)
- **Fonts/Icons:** Google Fonts (Playfair Display + Inter), Bootstrap Icons

## Features

**Public site** — Home, About, Menu (category filter), Gallery (type filter + lightbox), Location (map embed), Contact form.

**Customer accounts** — Registration/login with `password_hash()`/`password_verify()`, profile management, password change, order history, AJAX-powered cart.

**Checkout** — Pickup or delivery, cash payment, or realistic mock payment gateways for Online Banking (FPX-style) and Card, each with demo bank/card selection, demo OTP verification, a processing animation, and a distinct success/failed/cancelled result screen (with retry-on-failure), plus promo code discounts.

**Admin panel** — Product/category/gallery CRUD with validated image uploads, order management with status tracking, customer role management, dashboard with real sales stats, promo code management, and printable Daily/Weekly/Monthly sales reports plus per-order receipts.

**Security** — CSRF protection on every state-changing form, prepared statements throughout, session-based route guards, upload validation via `getimagesize()` (not just file extension), `.htaccess` hardening on sensitive directories, custom 404/500 error pages.

## Getting Started

1. Copy (or symlink) this project into your XAMPP `htdocs` folder.
2. Start Apache and MySQL via the XAMPP Control Panel.
3. Import `database/coffee_shop.sql` via phpMyAdmin — this creates the `coffee_shop_db` database, all tables, and seed data (sample categories, products, gallery items, and a demo promo code `WELCOME10`).
4. Visit `http://localhost/coffee-shop-system/` (adjust the folder name in `config/constants.php`'s `BASE_URL` if yours differs).
5. **Create an admin account:** register normally through the site, then promote that account via phpMyAdmin or the MySQL CLI:
   ```sql
   UPDATE users SET role = 'admin' WHERE email = 'your_email_here';
   ```
   Once one admin exists, further admins can be promoted directly from **Admin → Customers**.

All timestamps across the system are set to **GMT+8** (`config/constants.php` / `config/db.php`) — adjust if deploying elsewhere.

## Project Structure

```
coffee-shop-system/
├── admin/          Admin panel (products, categories, gallery, orders, customers, reports, promo codes)
├── ajax/           AJAX endpoints (cart, promo code validation)
├── assets/         CSS, JS, images
├── auth/           Register / login / logout
├── config/         DB connection + site-wide constants
├── customer/       Customer dashboard, cart, checkout, order history
├── database/       Full schema + seed data (coffee_shop.sql)
├── includes/       Shared components (header, navbar, footer, sidebars, product card/modal)
├── public/         Public storefront pages
└── uploads/        Admin-uploaded product & gallery images
```

## Development Roadmap

This project was built in two tracks: the core system, then a full UI/UX redesign layered on top.

### Track 1 — System Development

| # | Phase | Key Deliverables |
|---|---|---|
| 1 | Foundation & Auth | XAMPP setup, 9-table DB schema, `config/db.php`, header/navbar/footer, register/login/logout with CSRF + `password_hash()` |
| 2 | Public Website | Home, About, Menu, Gallery, Location, Contact |
| 3 | Customer Dashboard | Profile view/update, change password, order history |
| 4 | Menu + Cart | AJAX add/update/remove cart, live navbar cart badge |
| 5 | Checkout + Payment Gateway | Pickup/delivery, cash/online/card, simulated gateway with retry logic (later replaced — see Post-Launch Additions) |
| 6 | Admin: Products/Categories/Gallery | Full CRUD, image upload validated via `getimagesize()` |
| 7 | Admin: Order Management | Order list/filter, detail view, payment history, status updates |
| 8 | Admin: Customer Management | Role toggle with self-demotion + last-admin guards |
| 9 | Admin: Dashboard & Reports | Real sales/orders/customers stats, popular products, "Mark as Paid" for cash |
| 10 | Polish Pass | Validation hardening, 404/500 pages, `.htaccess` security, full security review |

### Track 2 — UI/UX Redesign

| # | Phase | Key Deliverables |
|---|---|---|
| A | Design System Foundation | Palette, Playfair Display + Inter, Bootstrap variable overrides, pill buttons, hover-lift cards |
| B | Navbar + Footer | Sticky navbar, transparent-over-hero with scroll fade, multi-column footer |
| C | Home Page Redesign | Why Choose Us, Story teaser, Promotions, restyled Featured Products/Reviews, CTA band |
| D | Menu + Gallery | Fixed a filter-button active-state bug, wired in `.price-tag` styling |
| E | Auth + Contact + Checkout | Auth wrapper + icons, fixed a padding bug, two-column Contact, selectable checkout option cards |
| F | Customer Dashboard | Sidebar user header, icon stat cards, upgraded empty states |
| G | Admin Panel | Sidebar user/role header, icon stat cards, global table polish across 11 pages |
| H | Animations + Responsive Check | Scroll fade-in with reduced-motion support, caught & fixed 2 missed pages (About, Location) |

### Post-Launch Additions

| Addition | What It Does |
|---|---|
| Printable reports | `admin/print_report.php` — Daily/Weekly/Monthly printable sales reports |
| Printable order receipt | `admin/print_order.php` — single-order printable receipt |
| GMT+8 timezone | Centralized in `config/constants.php` (PHP) and `config/db.php` (MySQL session) |
| Promo code system | Admin-managed discount codes (percentage/fixed, usage limit, expiry), live checkout preview, server-authoritative validation |
| Rebrand | Nōva Brew branding and logo |
| Mock payment gateways | Replaced the old "Simulate Success/Failure" buttons with realistic FPX-style and Card mock flows (`customer/payment_fpx.php`, `customer/payment_card.php`) — bank/card selection, demo login/OTP (`123456`=success, `000000`=failure), processing animation, and a distinct Successful/Failed/Cancelled result screen with a demo transaction reference. 100% simulated: no real gateway, processor, or bank is ever contacted, and no real card/banking data is collected or stored. Built on the Post-Redirect-Get pattern so refreshing or hitting back can never accidentally mark an order as paid. |

**18/18 planned phases complete across both tracks, plus post-launch feature additions** — every phase verified against real HTTP requests, real database writes, and real file uploads where relevant.
