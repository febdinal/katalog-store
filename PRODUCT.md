# Product

<!-- impeccable:product-schema 1 -->

## Platform

web

## Users

- **Public Visitors (Mobile-First):** Shoppers and browsers viewing product catalog on mobile devices (375px priority), tablets, and desktops. Goal: Quick discovery, filtering by category, checking item specs, prices, and stock availability.
- **Store Administrator:** Internal catalog manager accessing `/admin/`. Goal: Securely manage categories, product catalog, upload product photos, and monitor inventory levels.

## Product Purpose

A lightweight, high-performance, mobile-first product catalog web application built with PHP 8.2+ Native, SQLite, Vanilla CSS, and Vanilla JavaScript. It delivers an intuitive shopping catalog experience for visitors and an efficient management dashboard for catalog administrators without external framework overhead.

## Positioning

A zero-framework, dependency-free PHP catalog platform with strict security, instant SQLite storage, single public document root isolation, and a bespoke retail UI design optimized for mobile ergonomics.

## Operating Context

- **Environment:** PHP 8.2+ with SQLite3 extension, hosted on web servers (e.g. Laragon / Nginx / Apache / PHP built-in server) with `public/` as the single public document root.
- **Client Devices:** Viewports ranging from mobile phones (375px), tablets (768px), to desktop monitors (1024px+). Primary focus on mobile touch interactions (minimum touch target 44px).

## Capabilities and Constraints

### Public Catalog Capabilities
- **Product Grid:** Responsive layout (2 columns on 375px mobile, 3 columns on 768px tablet, 4 columns on desktop).
- **Category Filter:** Horizontal scrollable category pills on mobile using query strings (e.g. `?category=elektronik`).
- **Pagination:** 12 active products per page with clear empty states when no products match.
- **Product Information:** Displays product photo (4:5 ratio), name (max 2 lines), brand, price in Rupiah format (`Rp XXX.XXX`), quantity, and automatic stock status badge.
- **Automatic Stock Rules:** `quantity = 0` (Habis - Red badge), `quantity 1-5` (Stok Menipis - Orange badge), `quantity > 5` (Tersedia - Green badge).

### Admin Capabilities
- **Authentication:** Admin login/logout at `/admin/login.php` (no public registration).
- **Dashboard (`/admin/index.php`):** Metrics overview (total products, total categories, stock levels: available, low stock, out of stock).
- **Category Management (`/admin/categories/`):** Full CRUD (create, read, update, delete) with automatic unique slug generation and active status toggling.
- **Product Management (`/admin/products/`):** Full CRUD with search (by name or brand) and filtering (by category and stock condition).
- **Image Upload:** Secure upload handling for product photos (JPG, PNG, WebP up to 2MB, MIME verification via `finfo`, randomized filenames, automatic cleanup of replaced/deleted photos).

### Technical & Architectural Constraints
- **Stack:** PHP 8.2+ Native, SQLite, HTML5, Vanilla CSS, Vanilla JavaScript.
- **Strict Bans:** No Laravel, React, Vue, Angular, Bootstrap, Tailwind, or unnecessary Composer packages.
- **Forbidden Features:** No shopping cart, checkout, online payment, or user registration.
- **Structure:** `public/` directory is the ONLY document root. All database, application logic, and configuration reside safely outside `public/`.

## Brand Commitments

- **Typography:** Plus Jakarta Sans font via Google Fonts.
- **Color Palette:** Warm off-white background (`#F9F9FB`), dark navy text (`#0F172A`), clean neutral borders (`#E2E8F0`), and a consistent primary accent color (`#2563EB`).
- **Visual Design:** Retail-inspired clean grid, 4:5 image ratio (`object-fit: cover`), prominent price formatting, custom CSS variables for design tokens.
- **Anti-Patterns strictly avoided:** No generic gradients, no oversized hero banners, no nested card-in-card containers, no excessive box shadows, no overly rounded pills/radii, no superfluous icons.

## Evidence on Hand

- **Database Seed Data:** Seed script (`database/seed.php`) containing:
  - 1 Default Admin (`admin@katalog.test` / `password`).
  - 5 Initial Categories.
  - 20 Realistic Sample Products across categories with varied pricing, brands, and stock quantities.

## Product Principles

1. **Mobile-First Ergonomics:** Touch targets >= 44px, horizontally scrollable filters, fast touch response, clear visual hierarchy.
2. **Zero-Dependency Architecture:** Clean, readable native PHP 8.2+ with PDO SQLite and Vanilla CSS custom properties.
3. **Defense-in-Depth Security:** PDO prepared statements everywhere, `password_hash()` / `password_verify()`, session regeneration on login, CSRF tokens on all POST forms, `htmlspecialchars()` XSS protection, HttpOnly/SameSite session cookies, strict MIME file upload validation.
4. **Scannability & Price Prominence:** Product titles truncated to 2 lines max, stock badges clearly color-coded, price presented as primary visual metric.

## Accessibility & Inclusion

- High contrast text and stock indicators meeting WCAG AA standards.
- Visible, distinct focus ring states for keyboard and screen-reader navigation.
- Semantic HTML tags (`<main>`, `<nav>`, `<article>`, `<header>`, `<footer>`, `<form>`).
- Touch-friendly click targets across all interactive elements (minimum 44px x 44px).
