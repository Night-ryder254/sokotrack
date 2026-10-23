# SokoTrack

**A multi-vendor B2B wholesale platform for the Kenyan market.** One distributor supplies many retail vendors through a single system: ordering with transaction-safe stock handling, M-Pesa / bank transfer / credit-terms payments, loyalty tiers, and ordering over USSD.

Built with Laravel 12 as a portfolio project. The focus is on getting the business logic right (stock, credit, loyalty and payments stay consistent under concurrent orders and cancellations) rather than on surface polish alone.

## Features

### For vendors (retail customers)

- Self-registration with Terms & Conditions acceptance; re-acceptance is required whenever a new version is published
- Storefront with search, categories, AJAX cart, and product pages with verified-purchase reviews and star ratings
- Checkout with **M-Pesa STK Push**, **bank transfer**, or **credit terms** (enforced against the vendor's available credit)
- Loyalty points (1 point per KES 100) and Bronze / Silver / Gold tiers
- Printable and emailed receipts
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

## Tech stack

| Layer | Technology |
|---|---|
| Backend | Laravel 12, PHP 8.2+ |
| Frontend | Blade, Tailwind CSS (Laravel Breeze), Alpine.js, Vite |
| Database | MySQL 8 (MariaDB 10.6+ also works) |
| Payments | Safaricom Daraja API (M-Pesa STK Push) |
| USSD | Africa's Talking |
| Email | SMTP (Gmail app password in development) |

## Getting started

**Requirements:** PHP 8.2+, Composer 2, Node 18+, MySQL 8 (or MariaDB 10.6+).

```bash
git clone https://github.com/<your-username>/sokotrack.git
cd sokotrack
composer install
npm install
cp .env.example .env
php artisan key:generate
```

Create an empty database named `sokotrack` (character set `utf8mb4`) and set the `DB_*` values in `.env`. Then:

```bash
php artisan migrate --seed
php artisan storage:link
npm run build          # or: npm run dev
php artisan serve
```

Open http://127.0.0.1:8000. Optionally add product photos (see [Product photos](#product-photos)):

```bash
php artisan products:fetch-images
```

### Demo accounts

The seeder creates one account per role, plus about 40 generated vendors with order, payment and loyalty history. In local development the password for all seeded accounts is `password`.

| Role | Email |
|---|---|
| Admin | `admin@sokotrack.test` |
| Warehouse staff | `staff@sokotrack.test` |
| Vendor | `vendor@sokotrack.test` |

Seeded accounts are for local development only. Never seed a production database.

## Configuration

Copy `.env.example` to `.env`. The values specific to this project:

| Variable | Purpose |
|---|---|
| `APP_URL` | Public URL of the app (the ngrok URL when testing M-Pesa callbacks) |
| `DB_*` | Database connection |
| `MAIL_*` | SMTP settings for transactional emails |
| `MPESA_ENV` | `sandbox` or `production` |
| `MPESA_CONSUMER_KEY`, `MPESA_CONSUMER_SECRET` | Daraja app credentials |
| `MPESA_SHORTCODE`, `MPESA_PASSKEY` | STK Push shortcode and passkey |
| `MPESA_CALLBACK_URL` | Public URL of `/api/mpesa/stk-callback` |

Never commit `.env`. See [docs/INTEGRATIONS.md](docs/INTEGRATIONS.md) for the full setup of each integration.

## Integrations

| Integration | Status |
|---|---|
| M-Pesa STK Push | Working end to end against the Daraja **sandbox** |
| Transactional email | Working (six order, payment and account emails, plus low-stock alerts) |
| USSD ordering | Working in the Africa's Talking **simulator**; a live service code needs a leased shortcode |
| M-Pesa C2B paybill | Not implemented. The Daraja sandbox app had no C2B product provisioned |

Details, flows and sandbox caveats are in [docs/INTEGRATIONS.md](docs/INTEGRATIONS.md).

## Scheduled tasks

The daily low-stock digest (`sokotrack:low-stock-digest`, 07:00) runs through Laravel's scheduler:

```bash
php artisan schedule:work                      # local development
* * * * * php artisan schedule:run             # production crontab entry
```

## Product photos

Product photos are not stored in the repository. The `products:fetch-images` command finds a free-licensed photo for each product, trying in order:

1. **The exact product** from [Open Food Facts](https://world.openfoodfacts.org), Open Beauty Facts and Open Products Facts (real packaging photos). The brand must match, and flavour variants such as "Zero" are rejected.
2. **A clean photo of the product type** from Wikipedia (for example, the article "Cheese"). This is only used for types where the lead photo is a clear product picture.
3. **A similar product** of the same type from the Open Facts databases (skip with `--exact-only`).

If nothing suitable is found the product keeps its placeholder rather than a wrong photo. Photos uploaded through the admin form are never overwritten.

```bash
php artisan products:fetch-images --preview     # builds a review page, changes nothing
php artisan products:fetch-images               # applies it
php artisan products:fetch-images --only="Milk" # one product or a few
```

The first run takes around 10 minutes because Open Food Facts limits searches; results are cached for a week. Before running it, set your email in the `USER_AGENT` constant of the command, as both Wikimedia and Open Facts ask clients to identify themselves.

Author and licence details for every photo are written to `storage/app/public/products/CREDITS.txt`.

## Testing

```bash
php artisan test
```

The current suite is unit tests for the product-photo matching logic (brand detection, product-type rules and the Open Facts matcher), run against the full seeded catalogue. Feature tests for checkout, cancellation and payment callbacks are on the roadmap.

## Project structure

```
app/
  Console/Commands/     low-stock digest, product photo fetcher
  Http/Controllers/     storefront, checkout, M-Pesa, USSD, reviews, terms
  Http/Controllers/Admin/  orders, vendors, staff, terms versions
  Http/Middleware/      role, vendor and terms-accepted gates
  Mail/                 transactional emails
  Models/               Eloquent models
  Services/             DarajaService (M-Pesa), VendorCreditScoreService
  Support/              product-photo matching (pure PHP, unit tested)
database/               migrations and seeders
docs/                   architecture and integration notes
resources/views/        Blade views and email templates
routes/                 web.php, api.php (M-Pesa and USSD callbacks), console.php
tests/Unit/             unit tests
```

## Design decisions

The reasoning behind the main decisions (row-locked checkout, signed ledgers for stock and loyalty, asynchronous payments, side effects after commit) is written up in [docs/ARCHITECTURE.md](docs/ARCHITECTURE.md).

## Known limitations

- **Sandbox integrations.** M-Pesa and USSD run against Safaricom's and Africa's Talking's sandboxes. Going live needs Daraja production credentials, a leased USSD shortcode and a public HTTPS deployment.
- **Failed M-Pesa payments leave a pending order.** The payment is marked failed, but the order keeps its stock and loyalty points until an admin cancels it. Automatic release and a vendor retry flow are planned.
- **The USSD callback is unauthenticated.** It trusts the phone number it receives. Before production it needs an Africa's Talking IP allowlist or shared secret, plus a vendor PIN before ordering.
- **Limited test coverage.** See [Testing](#testing).
- **Product photos are generic or community-sourced**, not supplier-provided. Upload exact photos per product through the admin form.
- **The chatbot is rule-based**, with no AI service behind it.

## Roadmap

- Release stock and points automatically when an M-Pesa payment fails or times out
- Vendor-facing retry for failed payments
- Feature tests for checkout, cancellation and callbacks
- USSD hardening (callback authentication, PIN)
- Production deployment (server, HTTPS domain, queue worker, scheduler, backups)
- M-Pesa C2B paybill for credit repayments, once the product is available on the Daraja app

## Credits

- Product photos come from [Wikimedia Commons](https://commons.wikimedia.org) (free licences, credited in `CREDITS.txt`) and from [Open Food Facts](https://world.openfoodfacts.org), Open Beauty Facts and Open Products Facts (contributor-licensed, with credit; see their terms of use).
- Fonts: Fraunces, Inter and IBM Plex Mono.

Built by Nigel Matekwa Alufwani.
