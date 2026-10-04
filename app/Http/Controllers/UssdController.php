<?php

namespace App\Http\Controllers;

use App\Mail\NewOrderAlert;
use App\Mail\OrderReceipt;
use App\Models\LoyaltyTransaction;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\StockMovement;
use App\Models\User;
use App\Models\Vendor;
use App\Models\VendorTier;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class UssdController extends Controller
{
    /**
     * Africa's Talking POSTs sessionId, serviceCode, phoneNumber and `text` on every keypress.
     * `text` is the cumulative input for the session, joined by "*" (e.g. "3*SKU-1338-OKR*2"),
     * so this handler is stateless: we re-derive the current step from `text` every time.
     * Replies must be plain text starting with "CON " (keep session open) or "END " (close it).
     */
    public function handle(Request $request)
    {
        Log::info('USSD request', $request->only(['sessionId', 'serviceCode', 'phoneNumber', 'text']));

        $text = trim((string) $request->input('text', ''));
        $steps = $text === '' ? [] : explode('*', $text);

        $vendor = $this->findVendorByPhone((string) $request->input('phoneNumber', ''));

        if (! $vendor) {
            return $this->endSession('This number is not registered on SokoTrack. Register on our website to start ordering.');
        }

        if (empty($steps)) {
            return $this->mainMenu($vendor);
        }

        return match ($steps[0]) {
            '1' => $this->creditBalance($vendor),
            '2' => $this->recentOrders($vendor),
            '3' => $this->quickReorder($vendor, array_slice($steps, 1)),
            '4' => $this->loyaltyPoints($vendor),
            default => $this->endSession('Invalid option. Please dial again and choose 1 to 4.'),
        };
    }

    private function mainMenu(Vendor $vendor)
    {
        return $this->continueSession(
            "SokoTrack\n{$vendor->business_name}\n1. Credit balance\n2. Recent orders\n3. Quick reorder\n4. Loyalty points"
        );
    }

    private function creditBalance(Vendor $vendor)
    {
        $available = max(0, $vendor->availableCredit());

        return $this->endSession(
            "Credit limit: " . $this->kes($vendor->credit_limit) .
            "\nOutstanding: " . $this->kes($vendor->outstanding_balance) .
            "\nAvailable: " . $this->kes($available)
        );
    }

    private function recentOrders(Vendor $vendor)
    {
        $orders = $vendor->orders()->latest()->take(3)->get();

        if ($orders->isEmpty()) {
            return $this->endSession('You have no orders yet.');
        }

        $lines = $orders->map(
            fn (Order $order) => $order->order_number . "\n" . $this->kes($order->total_amount) . ' - ' . ucfirst($order->status)
        );

        return $this->endSession("Recent orders:\n" . $lines->implode("\n"));
    }

    private function loyaltyPoints(Vendor $vendor)
    {
        $tierName = $vendor->tier?->name ?? 'Bronze';
        $nextTier = VendorTier::where('min_points', '>', $vendor->loyalty_points)
            ->orderBy('min_points')
            ->first();

        $message = 'Loyalty points: ' . number_format($vendor->loyalty_points) . "\nTier: {$tierName}";

        if ($nextTier) {
            $message .= "\n" . number_format($nextTier->min_points - $vendor->loyalty_points) . " pts to {$nextTier->name}";
        } else {
            $message .= "\nYou are on the top tier.";
        }

        return $this->endSession($message);
    }

    /**
     * Steps after choosing "3":
     *   []                  -> ask for SKU
     *   [sku]               -> show product, ask quantity
     *   [sku, qty]          -> show summary, ask to confirm
     *   [sku, qty, 1 or 2]  -> place the order (credit terms) or cancel
     */
    private function quickReorder(Vendor $vendor, array $steps)
    {
        // Same rule as the web app: no ordering until the current T&Cs are accepted.
        if (! $vendor->hasAcceptedCurrentTerms()) {
            return $this->endSession('Please accept our updated Terms & Conditions on the SokoTrack website or app before ordering.');
        }

        if (count($steps) === 0) {
            return $this->continueSession("Enter product SKU\n(e.g. SKU-1338-OKR):");
        }

        $product = $this->findProduct($steps[0]);

        if (! $product) {
            return $this->endSession('Product not found or out of stock. Please check the SKU and try again.');
        }

        if (count($steps) === 1) {
            return $this->continueSession(
                "{$product->name}\n" . $this->kes($product->price) . " each\nIn stock: {$product->stock_quantity}\nEnter quantity:"
            );
        }

        $qtyInput = trim($steps[1]);

        if (! ctype_digit($qtyInput) || (int) $qtyInput < 1) {
            return $this->endSession('Invalid quantity. Please start again and enter a whole number.');
        }

        $qty = (int) $qtyInput;

        if ($qty > $product->stock_quantity) {
            return $this->endSession("Only {$product->stock_quantity} in stock. Please start again with a smaller quantity.");
        }

        $total = round($product->price * $qty, 2);
        $available = max(0, $vendor->availableCredit());

        if ($total > $available) {
            return $this->endSession(
                "Insufficient credit.\nOrder: " . $this->kes($total) .
                "\nAvailable: " . $this->kes($available) .
                "\nPlease order via the app or website."
            );
        }

        if (count($steps) === 2) {
            return $this->continueSession(
                "Confirm order:\n{$qty} x {$product->name}\nTotal: " . $this->kes($total) .
                "\nPay on credit\n1. Confirm\n2. Cancel"
            );
        }

        if ($steps[2] === '2') {
            return $this->endSession('Order cancelled.');
        }

        if ($steps[2] !== '1') {
            return $this->endSession('Invalid option. Order not placed.');
        }

        try {
            [$order, $lowStockProduct] = $this->placeCreditOrder($vendor, $product, $qty);
        } catch (\RuntimeException $e) {
            // Expected business failures (stock ran out, credit changed) carry a user-safe message.
            return $this->endSession($e->getMessage());
        } catch (\Throwable $e) {
            Log::error('USSD order failed', ['error' => $e->getMessage()]);

            return $this->endSession('Sorry, we could not place your order. Please try again or use the SokoTrack app.');
        }

        // Emails can take a few seconds over SMTP and USSD sessions are time-sensitive,
        // so reply to the phone first and send notifications after the response.
        defer(fn () => $this->notifyOrderPlaced($order, $lowStockProduct));

        return $this->endSession(
            "Order {$order->order_number} placed.\n" . $this->kes($order->total_amount) .
            " added to your credit balance.\nA receipt will be emailed to you."
        );
    }

    /**
     * Mirrors CheckoutController's credit path: row locks against overselling, a stock movement
     * for the audit trail, credit balance, loyalty points. Credit and stock are re-checked
     * inside the transaction, because the values read earlier may have changed since.
     *
     * @return array{0: Order, 1: ?Product} the order, and the product if this sale pushed it
     *                                      to/below its low-stock threshold
     */
    private function placeCreditOrder(Vendor $vendor, Product $product, int $qty): array
    {
        $crossedThreshold = false;
        $lockedProduct = null;

        $order = DB::transaction(function () use ($vendor, $product, $qty, &$crossedThreshold, &$lockedProduct) {
            $lockedVendor = Vendor::where('id', $vendor->id)->lockForUpdate()->first();
            $lockedProduct = Product::where('id', $product->id)->lockForUpdate()->first();

            if (! $lockedProduct || $lockedProduct->stock_quantity < $qty) {
                throw new \RuntimeException('Sorry, that item just went out of stock.');
            }

            $total = round($lockedProduct->price * $qty, 2);

            if ($total > $lockedVendor->availableCredit()) {
                throw new \RuntimeException('Insufficient credit for this order.');
            }

            $order = Order::create([
                'vendor_id' => $lockedVendor->id,
                'order_number' => 'ORD-' . strtoupper(uniqid()),
                'total_amount' => $total,
                'payment_method' => 'credit',
                'status' => 'confirmed',
            ]);

            OrderItem::create([
                'order_id' => $order->id,
                'product_id' => $lockedProduct->id,
                'quantity' => $qty,
                'unit_price' => $lockedProduct->price,
            ]);

            StockMovement::create([
                'product_id' => $lockedProduct->id,
                'quantity_change' => -$qty,
                'reason' => 'sale',
                'order_id' => $order->id,
                'notes' => 'Placed via USSD',
            ]);

            $previousStock = $lockedProduct->stock_quantity;
            $lockedProduct->decrement('stock_quantity', $qty);
            $lockedProduct->refresh();

            $crossedThreshold = $previousStock > $lockedProduct->low_stock_threshold
                && $lockedProduct->stock_quantity <= $lockedProduct->low_stock_threshold;

            $lockedVendor->increment('outstanding_balance', $total);

            $pointsEarned = intdiv((int) $total, 100);

            LoyaltyTransaction::create([
                'vendor_id' => $lockedVendor->id,
                'points_change' => $pointsEarned,
                'reason' => 'order_earned',
                'order_id' => $order->id,
            ]);

            $lockedVendor->increment('loyalty_points', $pointsEarned);

            return $order;
        });

        return [$order, $crossedThreshold ? $lockedProduct : null];
    }

    /**
     * Runs after the USSD response is sent. A mail failure here must never affect an
     * order that has already been committed, so it is logged and swallowed.
     */
    private function notifyOrderPlaced(Order $order, ?Product $lowStockProduct): void
    {
        try {
            if ($lowStockProduct) {
                app(ProductController::class)->sendLowStockAlert($lowStockProduct);
            }

            $order->load('vendor.user', 'items.product');

            Mail::to($order->vendor->user->email)->send(new OrderReceipt($order));

            $staffEmails = User::whereIn('role', ['admin', 'warehouse_staff'])->pluck('email');
            if ($staffEmails->isNotEmpty()) {
                Mail::to($staffEmails)->send(new NewOrderAlert($order));
            }
        } catch (\Throwable $e) {
            Log::warning('USSD order notifications failed', [
                'order' => $order->order_number,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Stored vendor phones come in mixed formats (+2547..., 2547..., 07...), and AT sends
     * "+2547XXXXXXXX". Matching on the last 9 digits works across all of them.
     */
    private function findVendorByPhone(string $phoneNumber): ?Vendor
    {
        $digits = preg_replace('/\D/', '', $phoneNumber);

        if (strlen($digits) < 9) {
            return null;
        }

        return Vendor::with(['tier', 'user'])
            ->where('phone', 'like', '%' . substr($digits, -9))
            ->first();
    }

    /**
     * Accepts "SKU-1338-OKR", "sku-1338-okr" or just "1338-OKR" (less typing on a keypad).
     */
    private function findProduct(string $input): ?Product
    {
        $sku = strtoupper(trim($input));

        if ($sku === '') {
            return null;
        }

        if (! str_starts_with($sku, 'SKU-')) {
            $sku = 'SKU-' . $sku;
        }

        return Product::where('sku', $sku)->where('stock_quantity', '>', 0)->first();
    }

    private function kes($amount): string
    {
        return 'KES ' . number_format((float) $amount, 2);
    }

    private function continueSession(string $message)
    {
        return $this->respond('CON ' . $message);
    }

    private function endSession(string $message)
    {
        return $this->respond('END ' . $message);
    }

    /**
     * USSD screens are capped at about 182 characters, so trim as a safety net.
     */
    private function respond(string $body)
    {
        return response(mb_substr($body, 0, 182), 200)->header('Content-Type', 'text/plain');
    }
}
