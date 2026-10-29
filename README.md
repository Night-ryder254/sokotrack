# SokoTrack

**A multi-vendor B2B wholesale platform for the Kenyan market.**

![Laravel 12](https://img.shields.io/badge/Laravel-12-FF2D20?logo=laravel&logoColor=white)
![PHP 8.2+](https://img.shields.io/badge/PHP-8.2%2B-777BB4?logo=php&logoColor=white)
![MySQL 8](https://img.shields.io/badge/MySQL-8-4479A1?logo=mysql&logoColor=white)
![Tailwind CSS](https://img.shields.io/badge/Tailwind_CSS-3-06B6D4?logo=tailwindcss&logoColor=white)
![Alpine.js](https://img.shields.io/badge/Alpine.js-3-8BC0D0?logo=alpinedotjs&logoColor=black)

SokoTrack lets one distributor supply many retail vendors through a single system: ordering with transaction-safe stock handling, payment by M-Pesa, bank transfer or credit terms, loyalty tiers, and ordering over USSD. It is a portfolio project, and the emphasis is on getting the business logic right. Stock, credit, loyalty points and payments stay consistent under concurrent orders, cancellations and failed or repeated payment callbacks.

## Table of contents

- [At a glance](#at-a-glance)
- [Features](#features)
- [Requirements](#requirements)
- [Getting started](#getting-started)
- [Configuration](#configuration)
- [Usage](#usage)
- [Integrations](#integrations)
- [Command reference](#command-reference)
- [Product photos](#product-photos)
- [Testing](#testing)
- [Architecture](#architecture)
- [Project structure](#project-structure)
- [Security](#security)
- [Data protection](#data-protection)
- [Deployment](#deployment)
- [Troubleshooting](#troubleshooting)
- [Known limitations](#known-limitations)
- [Roadmap](#roadmap)
- [Contributing](#contributing)
- [License](#license)
- [Acknowledgements](#acknowledgements)
- [Author](#author)

## At a glance

| | |
|---|---|
| **Users** | Vendors (retail customers), warehouse staff, admins |
| **Payment methods** | M-Pesa STK Push, bank transfer, credit terms |
| **Channels** | Web storefront and admin, USSD |
| **Backend** | Laravel 12, PHP 8.2+ |
| **Frontend** | Blade, Tailwind CSS (Laravel Breeze), Alpine.js, Vite |
| **Database** | MySQL 8 (MariaDB 10.6+ also works) |
| **Integrations** | Safaricom Daraja, Africa's Talking, SMTP email |
| **Currency** | Kenyan shillings (KES) |

## Features

### For vendors (retail customers)

- Self-registration with Terms & Conditions acceptance; re-acceptance is required whenever a new version is published
- Storefront with search, categories, AJAX cart, and product pages with verified-purchase reviews and star ratings
- Checkout with **M-Pesa STK Push**, **bank transfer**, or **credit terms** (enforced against the vendor's available credit)
- Loyalty points (1 point per KES 100) and Bronze / Silver / Gold tiers
- Printable and emailed receipts
- Failed or unanswered M-Pesa payments release the order's stock and loyalty points automatically
- **USSD menu**: credit balance, recent orders, quick reorder by SKU, loyalty points
- Support chatbot (FAQ-based, role-aware, with a WhatsApp fallback)

### For admins and warehouse staff

- Products and categories with photo uploads; stock adjustments are a separate, audit-logged action
- Order management: filter, confirm bank transfers, cancel with automatic reversal of stock, loyalty points and credit
- Vendor management: credit limits, tiers, manual loyalty adjustments, full order history
- **Vendor Credit Health Score**: Low / Medium / High risk from payment reliability, credit utilization and cancellations, with advisory credit-limit suggestions (never applied automatically)
- Staff accounts (admin / warehouse staff) with a guard against removing the last admin
- Terms & Conditions publishing with versioning
- Low-stock email alerts: real-time when a product crosses its threshold, plus a daily digest

### Roles

| Role | Access |
|---|---|
| **Vendor** | Storefront, cart, checkout, own orders and receipts, reviews, own dashboard, USSD |
| **Warehouse staff** | Internal operations screens, without the admin-only areas below |
| **Admin** | Everything above, plus vendor management, staff accounts and Terms & Conditions |

A vendor is a user with a vendor profile and the role `none`, so an external customer can never be granted staff access by accident. See [docs/ARCHITECTURE.md](docs/ARCHITECTURE.md).

## Requirements

| Requirement | Version | Notes |
|---|---|---|
| PHP | 8.2 or newer | Built and tested on 8.2 |
| PHP extensions | `ctype`, `curl`, `dom`, `fileinfo`, `filter`, `hash`, `mbstring`, `openssl`, `pcre`, `pdo`, `pdo_mysql`, `session`, `tokenizer`, `xml` | `pdo_sqlite` is also needed to run the test suite |
| Composer | 2.x | |
| Node.js and npm | Node 20 or newer | Builds the frontend with Vite |
| Database | MySQL 8.0+ or MariaDB 10.6+ | `utf8mb4` |
| Git | Any recent version | |

**Optional, for the integrations:**

| For | You need |
|---|---|
| M-Pesa STK Push | A Safaricom Daraja developer account (sandbox), and a tunnel such as [ngrok](https://ngrok.com) so Safaricom can reach your machine |
| USSD ordering | An Africa's Talking account (sandbox) and a tunnel |
| Real emails | An SMTP account. A Gmail address with an app password works for development |
| Product photos | Internet access, and no accounts or API keys |

**Development platform:** built on Windows with PHP from XAMPP and a standalone MySQL service. Every command below also works on macOS and Linux.

## Getting started

### 1. Clone and install

```bash
git clone https://github.com/<your-username>/sokotrack.git
cd sokotrack
composer install
npm install
```

### 2. Configure the environment

```bash
cp .env.example .env        # PowerShell: copy .env.example .env
php artisan key:generate
```

### 3. Create the database

```sql
CREATE DATABASE sokotrack CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
```

Set `DB_HOST`, `DB_PORT`, `DB_DATABASE`, `DB_USERNAME` and `DB_PASSWORD` in `.env` to match your MySQL server. See [Configuration](#configuration).

### 4. Migrate, seed and link storage

```bash
php artisan migrate --seed
php artisan storage:link
```

The seeder creates the demo accounts below, 6 categories, 60 products, about 40 vendors, and a couple of hundred orders with payment and loyalty history.

### 5. Build the frontend and run

```bash
npm run build               # or "npm run dev" for hot reload while developing
php artisan serve
```

Open http://127.0.0.1:8000.

### 6. Add product photos (optional)

```bash
php artisan products:fetch-images
```

See [Product photos](#product-photos) first, as it needs one small setting.

### Demo accounts

In local development the password for all seeded accounts is `password`.

| Role | Email |
|---|---|
| Admin | `admin@sokotrack.test` |
| Warehouse staff | `staff@sokotrack.test` |
| Vendor | `vendor@sokotrack.test` |

These are for local development only. Never seed a production database. The `.test` addresses cannot receive mail, so set `MAIL_MAILER=log` to read emails in `storage/logs/laravel.log`.

## Configuration

All configuration is in `.env`. Never commit it.

### Application and database

| Variable | Purpose |
|---|---|
| `APP_NAME`, `APP_ENV`, `APP_DEBUG` | Standard Laravel settings. Use `APP_ENV=production` and `APP_DEBUG=false` in production |
| `APP_URL` | Public URL of the app. Set it to the tunnel address when testing M-Pesa or USSD callbacks |
| `DB_CONNECTION` | `mysql` |
| `DB_HOST`, `DB_PORT` | Your MySQL server (the MySQL default port is 3306) |
| `DB_DATABASE`, `DB_USERNAME`, `DB_PASSWORD` | Database credentials. Quote the password if it contains `#` or spaces |

Sessions, cache and queues use the Laravel 12 defaults, which store them in the database. The migrations create those tables.

### Email

| Variable | Purpose |
|---|---|
| `MAIL_MAILER` | `smtp` to send, `log` to write emails to the log instead |
| `MAIL_HOST`, `MAIL_PORT`, `MAIL_ENCRYPTION` | SMTP server settings (Gmail: `smtp.gmail.com`, `587`, `tls`) |
| `MAIL_USERNAME`, `MAIL_PASSWORD` | SMTP login. For Gmail, an app password |
| `MAIL_FROM_ADDRESS`, `MAIL_FROM_NAME` | Sender shown on emails |

### M-Pesa (Daraja)

| Variable | Purpose |
|---|---|
| `MPESA_ENV` | `sandbox` or `production` |
| `MPESA_CONSUMER_KEY`, `MPESA_CONSUMER_SECRET` | Credentials of your Daraja app |
| `MPESA_SHORTCODE`, `MPESA_PASSKEY` | STK Push shortcode and passkey (Safaricom publishes the sandbox values) |
| `MPESA_CALLBACK_URL` | Public URL of `/api/mpesa/stk-callback` |

Full setup for each integration is in [docs/INTEGRATIONS.md](docs/INTEGRATIONS.md).

## Usage

### A quick tour

**As a vendor** (`vendor@sokotrack.test`):

1. Browse the shop, search or filter by category, and add products to the cart.
2. Check out with **credit terms** (confirmed immediately, within your available credit) or **bank transfer** (stays pending until an admin confirms it).
3. Open the receipt, and watch loyalty points and tier progress on the dashboard.
4. Leave a review on a product you have bought.

**As an admin** (`admin@sokotrack.test`):

1. Open **Orders**, filter by status or payment method, confirm a bank transfer, or cancel an order and see stock, points and credit reversed.
2. Open **Vendors** to see a vendor's order history, Credit Health Score and suggested credit limit, and to change their limit or tier.
3. Open **Terms & Conditions** and publish a new version: every vendor must accept it before continuing.

M-Pesa checkout and USSD need the integration setup in [docs/INTEGRATIONS.md](docs/INTEGRATIONS.md).

## Integrations

| Integration | Status |
|---|---|
| M-Pesa STK Push | Working end to end against the Daraja **sandbox** |
| Transactional email | Working: order receipt, new-order alert, payment confirmed, order cancelled, welcome, password reset, and low-stock alerts |
| USSD ordering | Working in the Africa's Talking **simulator**; a live service code needs a leased shortcode |
| M-Pesa C2B paybill | Not implemented. The Daraja sandbox app had no C2B product provisioned |

M-Pesa and USSD callbacks must be reachable from the internet, so local testing needs a tunnel:

```bash
php artisan serve
ngrok http 8000
```

Each ngrok restart gives a new address. Update `APP_URL` and `MPESA_CALLBACK_URL`, run `php artisan config:clear`, and update the callback URL in the Africa's Talking dashboard.

## Command reference

| Command | What it does |
|---|---|
| `php artisan migrate --seed` | Create the schema and demo data |
| `php artisan migrate:fresh --seed` | Rebuild everything from scratch (destroys all data) |
| `php artisan storage:link` | Expose uploaded and fetched photos at `/storage` |
| `php artisan schedule:work` | Run the scheduler locally (production uses a cron entry, see [Deployment](#deployment)) |
| `php artisan sokotrack:low-stock-digest` | Send the low-stock summary email now (scheduled daily at 07:00) |
| `php artisan orders:release-stale-mpesa` | Cancel M-Pesa orders unanswered for 15 minutes (scheduled every five minutes); `--minutes=N` changes the window |
| `php artisan products:fetch-images` | Fetch product photos (options: `--preview`, `--exact-only`, `--force`, `--only="name"`) |
| `php artisan test` | Run the test suite |
| `./vendor/bin/pint` | Format the code to the Laravel style |
| `npm run dev` / `npm run build` | Frontend with hot reload / production build |
| `php artisan route:list` | List every route |

## Product photos

Product photos are not stored in the repository. `products:fetch-images` finds a free-licensed photo for each product, trying in order:

1. **The exact product** from [Open Food Facts](https://world.openfoodfacts.org), Open Beauty Facts and Open Products Facts (real packaging photos). The brand must match, and flavour variants such as "Zero" are rejected.
2. **A clean photo of the product type** from Wikipedia (for example, the article "Cheese"). This is only used for types where the lead photo is a clear product picture.
3. **A similar product** of the same type from the Open Facts databases (skip with `--exact-only`).

If nothing suitable is found the product keeps its placeholder rather than a wrong photo. Photos uploaded through the admin form are never overwritten.

```bash
php artisan products:fetch-images --preview     # builds a review page, changes nothing
php artisan products:fetch-images               # applies it
php artisan products:fetch-images --only="Milk" # one product or a few
```

- **Set your email first.** Wikimedia and Open Facts ask clients to identify themselves, so change `you@example.com` in the `USER_AGENT` constant of `app/Console/Commands/FetchProductImages.php`.
- **The first run takes about 10 minutes** because Open Food Facts limits searches. Results are cached for a week, so later runs take seconds.
- **The preview** is written to `storage/app/public/products/preview.html`. Open `http://127.0.0.1:8000/storage/products/preview.html` to check every photo, its source, and whether it is an exact product, a product-type photo or a similar product.
- **Credits** for every photo are written to `storage/app/public/products/CREDITS.txt`.
- This command reads `.env` directly, so use it for local seeding and do not run it on a server with cached configuration.
- To teach it a new product type, add one line to `app/Support/ProductImageTerms.php`.

## Testing

```bash
php artisan test
```

The suite uses an in-memory SQLite database (set in `phpunit.xml`), so it never touches your MySQL data. It needs the `pdo_sqlite` PHP extension.

| Area | Type | What is covered |
|---|---|---|
| Product-photo matching | Unit | Brand detection, product-type rules and the Open Facts matcher, against the full seeded catalogue |
| M-Pesa payment outcomes | Feature | A failed payment cancels the order and returns stock and points; repeated callbacks are ignored; a late payment on a released order changes nothing; unanswered payments are released after the timeout |
| Authentication and registration | Feature | Login, logout, password reset with the branded email, vendor registration with the terms checkbox |
| Profile and email verification | Feature | Laravel Breeze's standard tests |

Checkout, admin order actions and the USSD flow are not yet covered by automated tests.

## Architecture

The reasoning behind the main decisions is written up in [docs/ARCHITECTURE.md](docs/ARCHITECTURE.md). In short:

- **Checkout is one transaction with row locks**, so two buyers cannot both take the last unit.
- **Stock and loyalty points are append-only signed ledgers**, so every change is auditable and totals can be re-derived.
- **Emails and external API calls happen after the commit**, never inside a transaction.
- **M-Pesa is asynchronous.** The order starts pending and the callback decides the outcome. The callback is idempotent, and every cancellation goes through one service.
- **The credit health score is advisory.** It suggests; it never changes a credit limit by itself.

The USSD menu, M-Pesa flow, email setup and a production checklist are in [docs/INTEGRATIONS.md](docs/INTEGRATIONS.md).

## Project structure

```
app/
  Console/Commands/        low-stock digest, stale M-Pesa release, product photo fetcher
  Http/Controllers/        storefront, checkout, M-Pesa, USSD, reviews, terms
  Http/Controllers/Admin/  orders, vendors, staff, terms versions
  Http/Middleware/         role, vendor and terms-accepted gates
  Mail/                    transactional emails
  Models/                  Eloquent models
  Notifications/           branded password reset
  Services/                DarajaService, OrderCancellationService, VendorCreditScoreService
  Support/                 product-photo matching (pure PHP, unit tested)
database/                  migrations, factories, seeders
docs/                      architecture and integration notes
resources/views/           Blade views and email templates
routes/                    web.php, api.php (M-Pesa and USSD callbacks), console.php (schedule)
tests/                     unit and feature tests
```

## Security

What is in place:

- Role-based access through middleware; a vendor can never reach staff screens
- Laravel's CSRF protection, hashed passwords, and session regeneration on login
- Mass assignment limited by `$fillable` on every model
- Row locking and transactions around stock, credit and payment changes
- Secrets only in `.env`, which is git-ignored

What you must do before going live:

- Keep `.env` out of version control and rotate any key that has ever been committed or shared
- Set `APP_DEBUG=false`
- Restrict `trustProxies` in `bootstrap/app.php` to your real proxy addresses (it trusts all proxies so that ngrok works in development)
- Protect the USSD callback with an Africa's Talking IP allowlist or shared secret, and require a vendor PIN before ordering
- Never seed the demo accounts in production

The M-Pesa callback is called by Safaricom and cannot use a login. Payments are matched by the unguessable `CheckoutRequestID` returned by Safaricom, and the callback only ever acts on a payment that is still pending.

**Reporting a vulnerability:** please report it privately to the author rather than opening a public issue.

## Data protection

SokoTrack stores personal data: vendor contact names, phone numbers, email addresses and order history. A production deployment needs a lawful basis for processing, a privacy notice, a retention policy and, where it applies, registration under the Kenya Data Protection Act, 2019. This repository is a portfolio project and does not claim compliance.

## Deployment

A production checklist is in [docs/INTEGRATIONS.md](docs/INTEGRATIONS.md#production-checklist). In outline:

1. A server with PHP 8.2+, the extensions above, MySQL, and a web server whose document root is `public/`. Serve it over HTTPS.
2. Set `.env` for production (`APP_ENV=production`, `APP_DEBUG=false`, real `APP_URL`, database and mail settings).
3. Install and build:

   ```bash
   composer install --no-dev --optimize-autoloader
   npm ci && npm run build
   php artisan migrate --force
   php artisan storage:link
   php artisan config:cache && php artisan route:cache && php artisan view:cache
   ```

4. Add the scheduler to cron, which runs the daily digest and the M-Pesa release:

   ```
   * * * * * cd /path/to/sokotrack && php artisan schedule:run >> /dev/null 2>&1
   ```

5. Switch to Daraja production credentials and a production callback URL, and complete Safaricom's go-live process. USSD needs a leased shortcode.

No queue worker is needed: emails are sent directly, and the USSD handler defers its emails until after the response. Do **not** run `db:seed` in production.

## Troubleshooting

| Problem | Likely cause and fix |
|---|---|
| `SQLSTATE[HY000] [2002] Connection refused` | The database is not running, or `DB_HOST` / `DB_PORT` in `.env` do not match it. Run `php artisan config:clear` after changing `.env` |
| `errno: 150` or "Cannot add foreign key constraint" during migration | A table is created before the table it references. `vendor_tiers` must migrate before `vendors`. Check the migration filenames sort in that order |
| `Class "..." not found` after adding a file | Run `composer dump-autoload`, and check the filename and folder case exactly match the class and namespace (Windows ignores case, Linux does not) |
| Product images do not show | Run `php artisan storage:link`, then `php artisan products:fetch-images` |
| 419 "Page Expired" when using ngrok | ngrok's first-visit page interrupts the session. Open the tunnel URL in a private window or clear cookies, and make sure `APP_URL` matches the tunnel |
| M-Pesa prompt or callback never arrives | The ngrok address changed (update `APP_URL` and `MPESA_CALLBACK_URL`, then `php artisan config:clear`), or the Daraja sandbox is unreliable. The pending order is released automatically after 15 minutes |
| Emails do not arrive | Seeded `.test` addresses cannot receive mail. Use a real address, or `MAIL_MAILER=log` and read `storage/logs/laravel.log` |
| `php artisan test` wipes or changes your real data | It should not. Check `phpunit.xml` sets `DB_CONNECTION=sqlite` and `DB_DATABASE=:memory:` |
| Changes to `.env` are ignored | Configuration is cached. Run `php artisan config:clear` |
| The database server keeps crashing | A database run from a control panel can stop uncleanly. Run MySQL as a system service instead |

## Known limitations

- **Sandbox integrations.** M-Pesa and USSD run against Safaricom's and Africa's Talking's sandboxes. Going live needs Daraja production credentials, a leased USSD shortcode and a public HTTPS deployment.
- **Payment outcomes depend on Safaricom's callback.** A failed payment releases the order immediately, and an unanswered one is released after 15 minutes. A payment that arrives after its order was released is logged for a manual refund rather than reviving the order.
- **The USSD callback is unauthenticated.** It trusts the phone number it receives. Before production it needs an IP allowlist or shared secret, plus a vendor PIN.
- **Single currency and a single distributor.** Prices are in KES and the app is not multi-tenant.
- **Limited test coverage.** See [Testing](#testing).
- **Product photos are generic or community-sourced**, not supplier-provided. Upload exact photos per product through the admin form.
- **The chatbot is rule-based**, with no AI service behind it.

## Roadmap

- Vendor-facing retry for failed payments
- Ask Safaricom's STK status API before releasing an unanswered order, instead of relying on a timeout
- Feature tests for checkout, admin order actions and USSD
- USSD hardening (callback authentication, PIN)
- Production deployment (server, HTTPS domain, backups)
- M-Pesa C2B paybill for credit repayments, once the product is available on the Daraja app

## Contributing

This is a portfolio project, but suggestions and bug reports are welcome through GitHub issues. To propose a change:

1. Fork the repository and create a branch from `main`.
2. Make the change, and add or update tests where it affects behaviour.
3. Run `./vendor/bin/pint` and `php artisan test`; both should pass.
4. Open a pull request describing what changed and why.

## Acknowledgements

- [Laravel](https://laravel.com), [Laravel Breeze](https://laravel.com/docs/starter-kits), [Tailwind CSS](https://tailwindcss.com), [Alpine.js](https://alpinejs.dev) and [Vite](https://vite.dev)
- [Safaricom Daraja](https://developer.safaricom.co.ke) for M-Pesa and [Africa's Talking](https://africastalking.com) for USSD
- Product photos from [Wikimedia Commons](https://commons.wikimedia.org) (free licences, credited in `CREDITS.txt`) and from [Open Food Facts](https://world.openfoodfacts.org), Open Beauty Facts and Open Products Facts (contributor-licensed, with credit; see their terms of use)
- Fonts: Fraunces, Inter and IBM Plex Mono

## Author

Built by **Nigel Matekwa Alufwani**
