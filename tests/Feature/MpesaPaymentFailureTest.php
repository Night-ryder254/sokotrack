<?php

namespace Tests\Feature;

use App\Mail\OrderReceipt;
use App\Models\Category;
use App\Models\LoyaltyTransaction;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Payment;
use App\Models\Product;
use App\Models\StockMovement;
use App\Models\User;
use App\Models\Vendor;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class MpesaPaymentFailureTest extends TestCase
{
    use RefreshDatabase;

    /**
     * All the test data is created here, in one place. If a table ever gains a required column,
     * a "NOT NULL constraint failed" error from this method names it: add it below and every test is fixed.
     *
     * A vendor who has just checked out with M-Pesa and is waiting for Safaricom:
     * 3 units already taken from stock (10 -> 7), 3 loyalty points already awarded,
     * and the payment pending.
     *
     * @return array{0: Order, 1: Product, 2: Vendor, 3: Payment}
     */
    private function pendingMpesaOrder(): array
    {
        $category = Category::forceCreate(['name' => 'Beverages', 'slug' => 'beverages']);

        $product = Product::forceCreate([
            'category_id' => $category->id,
            'name' => 'Test Cola 500ml',
            'sku' => 'SKU-TEST-001',
            'price' => 100,
            'stock_quantity' => 7,
            'low_stock_threshold' => 2,
        ]);

        $vendor = Vendor::forceCreate([
            'user_id' => User::factory()->create()->id,
            'business_name' => 'Test Vendor Ltd',
            'phone' => '254700000001',
            'credit_limit' => 0,
            'outstanding_balance' => 0,
            'loyalty_points' => 3,
        ]);

        $order = Order::forceCreate([
            'vendor_id' => $vendor->id,
            'order_number' => 'ORD-TEST-1',
            'total_amount' => 300,
            'payment_method' => 'mpesa',
            'status' => 'pending',
        ]);

        OrderItem::forceCreate([
            'order_id' => $order->id,
            'product_id' => $product->id,
            'quantity' => 3,
            'unit_price' => 100,
        ]);

        StockMovement::forceCreate([
            'product_id' => $product->id,
            'quantity_change' => -3,
            'reason' => 'sale',
            'order_id' => $order->id,
        ]);

        LoyaltyTransaction::forceCreate([
            'vendor_id' => $vendor->id,
            'points_change' => 3,
            'reason' => 'order_earned',
            'order_id' => $order->id,
        ]);

        $payment = Payment::forceCreate([
            'order_id' => $order->id,
            'method' => 'mpesa',
            'amount' => 300,
            'status' => 'pending',
            'checkout_request_id' => 'ws_CO_TEST',
        ]);

        return [$order, $product, $vendor, $payment];
    }

    private function failureCallback(int $resultCode = 1032): array
    {
        return ['Body' => ['stkCallback' => [
            'MerchantRequestID' => 'mr-1',
            'CheckoutRequestID' => 'ws_CO_TEST',
            'ResultCode' => $resultCode,
            'ResultDesc' => 'Request Cancelled by user.',
        ]]];
    }

    private function successCallback(): array
    {
        return ['Body' => ['stkCallback' => [
            'MerchantRequestID' => 'mr-1',
            'CheckoutRequestID' => 'ws_CO_TEST',
            'ResultCode' => 0,
            'ResultDesc' => 'The service request is processed successfully.',
            'CallbackMetadata' => ['Item' => [
                ['Name' => 'Amount', 'Value' => 300],
                ['Name' => 'MpesaReceiptNumber', 'Value' => 'QWE123RTY4'],
            ]],
        ]]];
    }

    public function test_a_failed_payment_cancels_the_order_and_releases_stock_and_points(): void
    {
        [$order, $product, $vendor, $payment] = $this->pendingMpesaOrder();

        $this->postJson('/api/mpesa/stk-callback', $this->failureCallback())
            ->assertOk()
            ->assertJson(['ResultCode' => 0]);

        $this->assertSame('failed', $payment->fresh()->status);
        $this->assertSame('cancelled', $order->fresh()->status);
        $this->assertEquals(10, $product->fresh()->stock_quantity);
        $this->assertEquals(0, $vendor->fresh()->loyalty_points);

        // Both ledgers stay auditable: the reversal is a new row, not an edit.
        $this->assertSame(1, StockMovement::where('order_id', $order->id)->where('reason', 'return')->count());
        $this->assertEquals(0, LoyaltyTransaction::where('order_id', $order->id)->sum('points_change'));
    }

    public function test_a_repeated_failure_callback_does_not_restock_twice(): void
    {
        [$order, $product, $vendor] = $this->pendingMpesaOrder();

        $this->postJson('/api/mpesa/stk-callback', $this->failureCallback())->assertOk();
        $this->postJson('/api/mpesa/stk-callback', $this->failureCallback())->assertOk();

        $this->assertEquals(10, $product->fresh()->stock_quantity);
        $this->assertEquals(0, $vendor->fresh()->loyalty_points);
        $this->assertSame(1, StockMovement::where('order_id', $order->id)->where('reason', 'return')->count());
    }

    public function test_a_successful_payment_confirms_the_order_and_sends_one_receipt_even_if_repeated(): void
    {
        Mail::fake();

        [$order, $product, , $payment] = $this->pendingMpesaOrder();

        $this->postJson('/api/mpesa/stk-callback', $this->successCallback())->assertOk();
        $this->postJson('/api/mpesa/stk-callback', $this->successCallback())->assertOk();

        $this->assertSame('confirmed', $payment->fresh()->status);
        $this->assertSame('QWE123RTY4', $payment->fresh()->mpesa_receipt_number);
        $this->assertSame('confirmed', $order->fresh()->status);
        $this->assertEquals(7, $product->fresh()->stock_quantity);

        Mail::assertSent(OrderReceipt::class, 1);
    }

    public function test_a_payment_that_arrives_after_the_order_was_released_changes_nothing(): void
    {
        Mail::fake();

        [$order, $product, , $payment] = $this->pendingMpesaOrder();

        $this->postJson('/api/mpesa/stk-callback', $this->failureCallback())->assertOk();
        $this->postJson('/api/mpesa/stk-callback', $this->successCallback())->assertOk();

        // The order must not come back to life; the late payment is logged for a manual refund.
        $this->assertSame('cancelled', $order->fresh()->status);
        $this->assertSame('failed', $payment->fresh()->status);
        $this->assertEquals(10, $product->fresh()->stock_quantity);

        Mail::assertNothingSent();
    }

    public function test_unanswered_mpesa_payments_are_released_after_the_timeout(): void
    {
        [$order, $product, $vendor, $payment] = $this->pendingMpesaOrder();

        Payment::whereKey($payment->id)->update(['created_at' => now()->subMinutes(30)]);

        $this->artisan('orders:release-stale-mpesa')->assertSuccessful();

        $this->assertSame('cancelled', $order->fresh()->status);
        $this->assertSame('failed', $payment->fresh()->status);
        $this->assertEquals(10, $product->fresh()->stock_quantity);
        $this->assertEquals(0, $vendor->fresh()->loyalty_points);
    }

    public function test_recent_mpesa_payments_are_left_alone(): void
    {
        [$order, $product, , $payment] = $this->pendingMpesaOrder();

        $this->artisan('orders:release-stale-mpesa')->assertSuccessful();

        $this->assertSame('pending', $order->fresh()->status);
        $this->assertSame('pending', $payment->fresh()->status);
        $this->assertEquals(7, $product->fresh()->stock_quantity);
    }

    public function test_confirmed_payments_are_never_released_however_old(): void
    {
        [$order, $product, , $payment] = $this->pendingMpesaOrder();

        Payment::whereKey($payment->id)->update(['status' => 'confirmed', 'created_at' => now()->subDay()]);
        Order::whereKey($order->id)->update(['status' => 'confirmed']);

        $this->artisan('orders:release-stale-mpesa')->assertSuccessful();

        $this->assertSame('confirmed', $order->fresh()->status);
        $this->assertEquals(7, $product->fresh()->stock_quantity);
    }
}
