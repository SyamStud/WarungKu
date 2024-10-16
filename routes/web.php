<?php

use Inertia\Inertia;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Illuminate\Foundation\Auth\EmailVerificationRequest;
use App\Http\Controllers\{
    Pos\PosController,
    ProfileController,
    PurchaseController,
    SettingController,
    RestockListController,
    UserSettingController,
    Admin\CartController,
    Admin\UserController,
    Admin\StockController,
    Admin\ReportController,
    Admin\ProductController,
    Admin\CartItemController,
    Admin\CategoryController,
    Admin\CustomerController,
    Admin\DebtItemController,
    Admin\DiscountController,
    Admin\SupplierController,
    Admin\DashboardController,
    Pos\DebtPaymentController,
    Admin\TransactionController,
    Admin\StockMovementController,
    Admin\DiscountProductController,
    Admin\TransactionItemController,
    Admin\DebtPaymentHistoryController
};
use App\Http\Controllers\Admin\DebtController;
use App\Http\Controllers\Admin\RestockController;
use App\Http\Controllers\Admin\UnitController;

// Email Verification Routes
Route::get('/email/verify', fn() => view('auth.verify-email'))
    ->middleware('auth')
    ->name('verification.notice');

Route::get('/email/verify/{id}/{hash}', fn(EmailVerificationRequest $request) => $request->fulfill() && redirect('/home'))
    ->middleware(['auth', 'signed'])
    ->name('verification.verify');

Route::post('/email/verification-notification', fn(Request $request) => $request->user()->sendEmailVerificationNotification() && back()->with('message', 'Verification link sent!'))
    ->middleware(['auth', 'throttle:6,1'])
    ->name('verification.send');

// Authentication Routes
Route::get('/', fn() => Inertia::render('Auth/Login'));

Route::get('/not-available-mobile', fn() => Inertia::render('NotAvailableMobile'))
    ->name('not-available-mobile');

// Profile Routes
Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

// Admin Routes
Route::middleware(['auth', 'verified'])->group(function () {
    // Admin Routes
    Route::middleware('role:admin')->group(function () {
        Route::prefix('admin')->group(function () {
            // Dashboard Routes
            Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
            Route::get('/dashboard/dashboardSummary', [DashboardController::class, 'dashboardSummary']);

            // Product Routes
            Route::get('/products/add-variant', [ProductController::class, 'addVariant'])->name('products.add.variant');
            Route::post('/products/add-variant', [ProductController::class, 'storeVariant'])->name('products.store.variant');
            Route::resource('/products', ProductController::class);

            // Purchase Routes
            Route::resource('/purchases', PurchaseController::class);
            Route::resource('/restock-lists', RestockListController::class)->middleware('check.mobile');
            Route::post('/restock-lists/print', [RestockListController::class, 'print']);

            // Other Admin Routes
            Route::resource('/users', UserController::class);
            Route::resource('/categories', CategoryController::class);
            Route::resource('/restocks', RestockController::class);
            Route::resource('/stock-movements', StockMovementController::class);
            Route::resource('/carts', CartController::class);
            Route::resource('/cart-items', CartItemController::class);
            Route::resource('/discount-products', DiscountProductController::class);
            Route::resource('/transactions', TransactionController::class);
            Route::resource('/discounts', DiscountController::class);
            Route::resource('/transaction-items', TransactionItemController::class);
            Route::resource('/customers', CustomerController::class);
            Route::resource('/debt-items', DebtItemController::class);
            Route::resource('/units', UnitController::class);
            Route::resource('/debts', DebtController::class);
            Route::post('/debts/delete', [DebtController::class, 'destroy']);

            // Supplier Routes
            Route::post('/suppliers/getByName', [SupplierController::class, 'getByName']);
            Route::resource('/suppliers', SupplierController::class);

            // Report Routes
            Route::get('/reports/transaction', [ReportController::class, 'transactionIndex']);
            Route::get('/reports/purchase', [ReportController::class, 'purchaseIndex']);

            // Debt Payment History
            Route::get('/debt-payment-history', [DebtPaymentHistoryController::class, 'index']);
        });
    });


    // POS Routes
    Route::post('/customers/getCustomer', [CustomerController::class, 'getCustomer']);
    Route::post('/products/getBySku', [ProductController::class, 'getProduct']);
    Route::post('/products/getByName', [ProductController::class, 'getProductByName']);
    Route::post('/products/getVariantByName', [ProductController::class, 'getProductVariantByName']);

    Route::prefix('pos')->group(function () {
        Route::resource('/', PosController::class);
        Route::get('/carts/getUserCart', [CartController::class, 'getUserCart']);
        Route::post('/carts/addProduct', [CartController::class, 'addProduct']);
        Route::post('/carts/addItem', [CartController::class, 'addItem']);
        Route::post('/carts/updateProduct', [CartController::class, 'updateProduct']);
        Route::post('/carts/removeProduct', [CartController::class, 'removeProduct']);
        Route::post('/carts/revoke', [CartController::class, 'revoke']);
        Route::post('/carts/updateVariant', [CartController::class, 'updateVariant']);
        Route::post('/carts/store-transaction', [CartController::class, 'storeTransaction']);
        Route::get('/debt-payments', [DebtPaymentController::class, 'index']);
        Route::post('/debt-payments/store-payment', [DebtPaymentController::class, 'storePayment']);
    });

    // Settings Routes
    Route::resource('/userSettings', UserSettingController::class);
    Route::resource('/globalSettings', SettingController::class);
    Route::get('/settings/getSettings', [SettingController::class, 'getSettings']);
    Route::post('/settings/updateSettings', [SettingController::class, 'updateSettings']);
    Route::resource('/settings', SettingController::class);

    // Purchase Routes
    Route::resource('/purchases', PurchaseController::class);
});

require __DIR__ . '/auth.php';
