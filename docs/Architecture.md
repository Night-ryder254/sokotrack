# Architecture and design decisions

This document explains how SokoTrack is put together and why, with the emphasis on the parts where correctness matters most: stock, credit, loyalty and payments.

## Domain model

| Table | Purpose |
|---|---|
| `users` | Login accounts. `role` is `admin`, `warehouse_staff` or `none` |
| `vendors` | Retail customers. Linked to a user; holds `credit_limit`, `outstanding_balance`, `loyalty_points`, tier |
| `vendor_tiers` | Bronze / Silver / Gold with a points threshold and discount |
| `categories`, `products` | Catalogue. `products.stock_quantity` is the current stock |
| `orders`, `order_items` | An order and its lines. `order_items.unit_price` is a snapshot of the price at order time |
| `payments` | One per paid order: method, status, M-Pesa receipt and request IDs |
| `stock_movements` | Signed ledger of every stock change, with a reason (`restock`, `sale`, `adjustment`, `return`) |
| `loyalty_transactions` | Signed ledger of every points change (`order_earned`, `redeemed`, `admin_adjustment`) |
| `reviews` | One rating per vendor per product |
| `terms_versions`, `terms_acceptances` | Versioned Terms & Conditions and who accepted which version |

**Vendors are separate from user roles.** Staff are users with an internal role. A vendor is a user that has a `vendors` row, and its `role` stays `none`. This keeps the external customer model apart from internal permissions, so a vendor can never be granted staff access by accident.

## Access control

Three middleware gates, applied per route group:

- `role:admin` or `role:admin,warehouse_staff` for internal screens
- `vendor` for the storefront, cart, checkout and reviews
- `terms.accepted` on every vendor route except the terms page itself, so a vendor who has not accepted the current terms is redirected there before doing anything else

## Orders and stock integrity

Checkout runs in a single database transaction. Each product row is locked with `lockForUpdate()` before its stock is checked and decremented, so two simultaneous checkouts cannot both buy the last unit. Within the same transaction the app writes the order, its lines, a `sale` stock movement per line, the payment record, the loyalty ledger entry and the vendor's credit balance.

Two rules follow from this:

- **Ledgers are append-only and signed.** A stock or points change is a new row with a positive or negative number, never an edit. Totals can always be re-derived and audited.
- **Side effects happen after the commit.** Emails and low-stock alerts are sent only once the transaction has succeeded. Sending inside the transaction would announce changes that might still roll back.

## Payments and order states

| Payment method | Order starts as | Becomes confirmed when |
|---|---|---|
| Credit terms | `confirmed` | Immediately; the amount is added to the vendor's outstanding balance |
| Bank transfer | `pending` | An admin confirms the transfer |
| M-Pesa | `pending` | Safaricom's STK callback reports success |

M-Pesa is asynchronous. Checkout creates the order and a pending payment, commits, and only then asks Safaricom to send the prompt (no external API call is made inside a database transaction). The customer lands on a pending page that polls `/api/mpesa/status` every 3 seconds. After 90 seconds it shows a "taking longer" message but keeps polling, so a late callback still completes the page.

## Cancellation

Cancelling an order, in one locked transaction, restores stock (a `return` movement per line), reverses the loyalty points earned (a negative ledger entry), reduces the outstanding balance for credit orders, and marks an unconfirmed payment as failed. A confirmed payment is left as it was, since money already moved.

## Credit and loyalty

- Available credit is `credit_limit - outstanding_balance`. Credit orders above it are refused at checkout, and re-checked inside the transaction.
- Points are earned at 1 per KES 100 (whole-number division) and recorded in the ledger.

## Vendor Credit Health Score

A live score from data the app already has, with no extra tables:

```
score = payment reliability x 50
      + (1 - credit utilization, capped at 1) x 30
      + (1 - cancellation rate) x 20
```

75 or more is Low risk, 50 to 74 Medium, below 50 High. A vendor with an outstanding balance but no credit limit counts as maximum utilization. The page may show a suggested credit limit change (increase for reliable, heavily used accounts; decrease for high-risk ones). Suggestions are advisory and never applied automatically.

## Terms & Conditions

Only one `terms_versions` row is active at a time. A vendor has accepted the current terms only if an acceptance row exists for the active version. Publishing a new version deactivates the old one in the same transaction, which makes every earlier acceptance stop counting and forces re-acceptance.

## Notifications

- Receipt and admin new-order alert: sent at checkout for credit and bank transfer, and from the confirmed callback for M-Pesa.
- Payment confirmed and order cancelled: sent from the admin order actions.
- Welcome email: after registration. Password reset: a branded override of Laravel's notification.
- Low stock fires once, at the moment a stock change takes a product from above its threshold to at or below it (manual adjustments and checkout), not on every later sale. A scheduled command sends a daily digest.

## Reviews

A vendor can review a product only if they have a confirmed order containing it. Resubmitting updates their review, so there is at most one per vendor per product. Averages and counts for the storefront grid come from `withAvg` / `withCount`, avoiding a query per product card.

## Support chatbot

A client-side widget. The FAQ set is built on the server per role (vendor or internal staff) and passed in as JSON, matched by keyword in the browser, with a pre-filled WhatsApp link when nothing matches. No external AI service is involved.

## Product photos

A pure-PHP matcher (`app/Support`) turns a product name into a brand and a product type. `products:fetch-images` uses them to find an exact-product photo in the Open Facts databases (brand must match; flavour variants and other brands are rejected; the nearest pack size wins), then a clean Wikipedia photo of the product type, then a similar product. The matching logic has no framework dependencies and is unit tested against the full seeded catalogue.

## Conventions worth knowing

- Every model declares `$fillable`. A column missing from it makes `update()` silently ignore that field, which caused a real bug during the M-Pesa work.
- Migration order matters under MySQL. `vendor_tiers` must run before `vendors` because of the foreign key.
- `trustProxies(at: '*')` in `bootstrap/app.php` exists so the app works behind an ngrok tunnel in development. Restrict it to your real proxy addresses in production.
