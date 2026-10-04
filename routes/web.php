<?php

use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\StorefrontController;
use App\Http\Controllers\CartController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\CheckoutController;
use App\Http\Controllers\Admin\StaffController;
use App\Http\Controllers\Admin\OrderController;
use App\Http\Controllers\Admin\VendorController;
use App\Http\Controllers\Admin\TermsVersionController;
use App\Http\Controllers\ReviewController;
use App\Http\Controllers\TermsController;

Route::get('/', function () {
    return auth()->check() ? redirect()->route('dashboard') : redirect()->route('login');
});

Route::get('/dashboard', [DashboardController::class, 'index'])->middleware(['auth', 'verified'])->name('dashboard');

Route::get('/terms-preview', function () {
    $currentVersion = \App\Models\TermsVersion::where('is_active', true)->firstOrFail();
    return view('terms.preview', compact('currentVersion'));
})->name('terms.preview');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

Route::middleware(['auth', 'role:admin'])->prefix('admin')->name('admin.')->group(function () {
    Route::get('/staff', [StaffController::class, 'index'])->name('staff.index');
    Route::get('/staff/create', [StaffController::class, 'create'])->name('staff.create');
    Route::post('/staff', [StaffController::class, 'store'])->name('staff.store');
    Route::patch('/staff/{user}', [StaffController::class, 'update'])->name('staff.update');
    Route::get('/vendors', [VendorController::class, 'index'])->name('vendors.index');
    Route::get('/vendors/{vendor}', [VendorController::class, 'show'])->name('vendors.show');
    Route::patch('/vendors/{vendor}', [VendorController::class, 'update'])->name('vendors.update');
    Route::post('/vendors/{vendor}/loyalty-adjustment', [VendorController::class, 'adjustLoyalty'])->name('vendors.loyalty-adjustment');
    Route::get('/terms', [TermsVersionController::class, 'index'])->name('terms.index');
    Route::get('/terms/create', [TermsVersionController::class, 'create'])->name('terms.create');
    Route::post('/terms', [TermsVersionController::class, 'store'])->name('terms.store');
});

Route::middleware(['auth', 'role:admin,warehouse_staff'])->group(function () {
    Route::resource('products', ProductController::class);
    Route::resource('categories', CategoryController::class);
    Route::post('products/{product}/adjust-stock', [ProductController::class, 'adjustStock'])->name('products.adjust-stock');
});

Route::middleware(['auth', 'role:admin,warehouse_staff'])->prefix('admin')->name('admin.')->group(function () {
    Route::get('/orders', [OrderController::class, 'index'])->name('orders.index');
    Route::get('/orders/{order}', [OrderController::class, 'show'])->name('orders.show');
    Route::patch('/orders/{order}/confirm-payment', [OrderController::class, 'confirmPayment'])->name('orders.confirm-payment');
    Route::patch('/orders/{order}/cancel', [OrderController::class, 'cancel'])->name('orders.cancel');
});

// Unguarded by terms.accepted — this is the page that RESOLVES the block,
// so it can never require the thing it exists to satisfy.
Route::middleware(['auth', 'vendor'])->group(function () {
    Route::get('/terms', [TermsController::class, 'show'])->name('terms.show');
    Route::post('/terms/accept', [TermsController::class, 'accept'])->name('terms.accept');
});

// Everything else a vendor does is gated behind having accepted current terms.
Route::middleware(['auth', 'vendor', 'terms.accepted'])->group(function () {
    Route::get('/shop', [StorefrontController::class, 'index'])->name('storefront.index');
    Route::get('/cart', [CartController::class, 'index'])->name('cart.index');
    Route::get('/shop/{product}', [StorefrontController::class, 'show'])->name('storefront.show');
    Route::post('/cart/{product}', [CartController::class, 'add'])->name('cart.add');
    Route::delete('/cart/{product}', [CartController::class, 'remove'])->name('cart.remove');
    Route::get('/checkout', [CheckoutController::class, 'index'])->name('checkout.index');
    Route::post('/checkout', [CheckoutController::class, 'store'])->name('checkout.store');
    Route::get('/checkout/{order}/pending', [CheckoutController::class, 'pending'])->name('checkout.pending');
    Route::get('/checkout/{order}/success', [CheckoutController::class, 'success'])->name('checkout.success');
    Route::post('/products/{product}/reviews', [ReviewController::class, 'store'])->name('reviews.store');
    Route::delete('/reviews/{review}', [ReviewController::class, 'destroy'])->name('reviews.destroy');
});

require __DIR__.'/auth.php';
