# Integrations

Setup, flows and sandbox caveats for each external service.

## M-Pesa STK Push (Safaricom Daraja)

### Setup

1. Create an app at the Daraja developer portal and note the sandbox consumer key and secret.
2. Set in `.env`:

```env
MPESA_ENV=sandbox
MPESA_CONSUMER_KEY=...
MPESA_CONSUMER_SECRET=...
MPESA_SHORTCODE=174379
MPESA_PASSKEY=...            # Safaricom's public sandbox passkey
MPESA_CALLBACK_URL=https://<your-tunnel>/api/mpesa/stk-callback
```

3. Safaricom must reach your callback over the public internet, so local development needs a tunnel (ngrok):

```bash
php artisan serve
ngrok http 8000
```

Each time ngrok restarts it gives a new address. Update `APP_URL` and `MPESA_CALLBACK_URL` in `.env` and run `php artisan config:clear`.

### Flow

1. Checkout creates the order and a pending payment, then calls `DarajaService::stkPush()` after the transaction commits.
2. Safaricom accepts the request and returns a `CheckoutRequestID`, stored on the payment.
3. The customer enters their PIN on the phone.
4. Safaricom POSTs the result to `/api/mpesa/stk-callback`. `MpesaController` finds the payment by `checkout_request_id`. The payment row is locked, and the callback acts only while it is still pending, so repeats are ignored. On success it stores the receipt number, confirms the payment and order, and sends the receipt and admin alert. On failure it marks the payment failed and cancels the order, returning the stock and loyalty points.
5. The pending page polls `/api/mpesa/status` and moves to a confirmation or failure screen.

### Failed and unanswered payments

- **The prompt could not be sent:** checkout cancels the order immediately and returns the vendor to checkout with the cart intact, so they can try again.
- **The customer cancels, enters a wrong PIN, or the prompt times out:** Safaricom reports a failure result code, and the callback cancels the order.
- **No callback arrives:** `orders:release-stale-mpesa` (every five minutes) cancels M-Pesa orders still unanswered after 15 minutes. Change the window with `--minutes`.
- **A success arrives after the order was released:** the customer has been charged for an order that no longer exists. The app does not revive it; it logs an error with the order, amount and M-Pesa receipt so the payment can be refunded manually.

### Sandbox caveats

- Delivery in the sandbox is not fully reliable. The request can be accepted but the phone prompt may not arrive, or the prompt may arrive and the callback may not. When no callback arrives, the order is released automatically after 15 minutes. The success path is verified by a real end-to-end run (PIN entered, real receipt, correct callback, email sent).
- Result code `1037` ("DS timeout, user cannot be reached") and `1032` (cancelled by user) are normal outcomes and mark the payment failed.
- Amounts are rounded to whole shillings because the sandbox rejects decimals.

### Not implemented: C2B paybill

A C2B flow (vendors repaying credit by paying to a paybill) was built and tested, then removed. The Daraja sandbox app did not offer the C2B product, so the URL registration call could not succeed. It can be revisited once the product is available.

## Email

SMTP, configured through the standard `MAIL_*` variables. For development with Gmail, enable 2-step verification and create an app password.

```env
MAIL_MAILER=smtp
MAIL_HOST=smtp.gmail.com
MAIL_PORT=587
MAIL_USERNAME=you@gmail.com
MAIL_PASSWORD="your-16-character-app-password"
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS="you@gmail.com"
MAIL_FROM_NAME="SokoTrack"
```

Use `MAIL_MAILER=log` to write emails to `storage/logs/laravel.log` instead of sending them. Note that seeded `.test` addresses are not deliverable, so SMTP will bounce mail sent to them.

## USSD (Africa's Talking)

### Setup

1. Create an Africa's Talking account (it starts in sandbox mode).
2. In the dashboard, create a USSD channel on the shared sandbox code. Set its callback URL to `https://<your-tunnel>/api/ussd`. The dashboard shows the dialable code, such as `*384*12345#`.
3. Open the USSD simulator, enter a vendor's phone number as it is stored in the database, and dial the code.

No API key is needed, because Africa's Talking calls your server and reads the reply.

### How it works

Africa's Talking POSTs `sessionId`, `phoneNumber` and `text` on every keypress, where `text` is the whole input so far joined by `*`. The handler is stateless: it rebuilds the current step from `text` each time. Replies start with `CON` (keep the session open) or `END` (close it) and are capped at 182 characters.

| Option | What it does |
|---|---|
| 1 | Credit limit, outstanding balance, available credit |
| 2 | The three most recent orders |
| 3 | Quick reorder: SKU, quantity, confirm. Pays on credit only |
| 4 | Loyalty points, tier, and points to the next tier |

Vendors are matched by the last 9 digits of their phone number, so `+2547...`, `2547...` and `07...` all resolve to the same vendor. Quick reorder uses the same row locking, stock movement, credit and loyalty logic as web checkout, and respects the Terms & Conditions gate. Receipt and admin emails are sent after the reply, so SMTP latency cannot time out the session.

### Before going live

The sandbox code only works inside the simulator. Production needs a leased shortcode from Africa's Talking and a public HTTPS deployment. The callback must also be hardened: it currently trusts the phone number it receives, so add an Africa's Talking IP allowlist or shared secret, and require a vendor PIN before placing an order.

## Production checklist

- PHP 8.2.35, 8.3.36, 8.4.27 or 8.5.12 or newer, with PHP-FPM or Apache restarted after the update (PHP2Shell, CVE-2026-109618 and CVE-2026-109619). On Windows, use the rebuilt `-1` packages, which add the Brotli fix for CVE-2026-111497. Until PHP ships a fix for CVE-2026-115560, set `session.upload_progress.enabled=0`
- `composer audit` shows no advisories for the installed packages
- Public HTTPS domain; set `APP_ENV=production`, `APP_DEBUG=false`, then `php artisan config:cache`, `route:cache` and `view:cache`
- MySQL with a dedicated, least-privilege user and regular backups
- Scheduler running (`* * * * * php artisan schedule:run`) for the daily low-stock digest
- Daraja production credentials and a production callback URL; complete Safaricom's go-live process
- Restrict `trustProxies` to your real proxy addresses
- A transactional email provider with a verified sending domain
- The photo command reads `env()` directly and is meant for local seeding. Do not run it on a server with cached config.
