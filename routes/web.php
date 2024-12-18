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
    Admin\DebtPaymentHistoryController,
    StoreController,
    StoreSettingController,
    SuperAdmin\StoreController as SuperStoreController,
};
use App\Http\Controllers\Admin\DebtController;
use App\Http\Controllers\Admin\ProductVariantController;
use App\Http\Controllers\Admin\RestockController;
use App\Http\Controllers\Admin\UnitController;
use App\Http\Controllers\SuperAdmin\AdsController;
use App\Http\Controllers\SuperAdmin\StoreApplicationController;

// Email Verification Routes
Route::get('/email/verify', fn() => view('auth.VerifyEmail'))
    ->middleware('auth')
    ->name('verification.notice');

Route::get('/email/verify/{id}/{hash}', fn(EmailVerificationRequest $request) => $request->fulfill() && redirect('/home'))
    ->middleware(['auth', 'signed'])
    ->name('verification.verify');

Route::post('/email/verification-notification', fn(Request $request) => $request->user()->sendEmailVerificationNotification() && back()->with('message', 'Verification link sent!'))
    ->middleware(['auth', 'throttle:6,1'])
    ->name('verification.send');

// Authentication Routes
Route::get('/', fn() => Inertia::render('Auth/Login'))->middleware('guest')->name('login');

Route::get('/not-available-mobile', fn() => Inertia::render('NotAvailableMobile'))
    ->name('not-available-mobile');

Route::get('/no-store', fn() => Inertia::render('NoStore'))
    ->name('noStore')->middleware('check.store.status');

Route::get('/register-store', fn() => Inertia::render('RegisterStore'))
    ->name('registerStore')->middleware('check.store.status');

Route::get('/waiting-approval', fn() => Inertia::render('WaitingApproval'))
    ->name('waiting.approval')->middleware('check.store');

// Profile Routes
Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

Route::resource('/stores', StoreController::class);

// Admin Routes
Route::middleware(['auth', 'verified', 'check.store', 'check.store.status'])->group(function () {
    // Admin Routes
    Route::prefix('admin')->group(function () {

        Route::middleware('role:admin|input-staff')->group(function () {
            Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
            Route::get('/dashboard/dashboardSummary', [DashboardController::class, 'dashboardSummary']);

            // Product Routes
            Route::get('/products/add-variant', [ProductController::class, 'addVariant'])->name('products.add.variant');
            Route::post('/products/add-variant', [ProductController::class, 'storeVariant'])->name('products.store.variant');
            Route::get('/products/excel-export', [ProductController::class, 'exportExcel']);
            Route::resource('/products', ProductController::class);

            Route::get('/product-variants/export-excel', [ProductVariantController::class, 'exportExcel']);
            Route::resource('/product-variants', ProductVariantController::class);

            Route::resource('/categories', CategoryController::class);
            Route::resource('/units', UnitController::class);
        });

        Route::middleware('role:admin')->group(function () {
            // Purchase Routes
            Route::resource('/purchases', PurchaseController::class);
            Route::resource('/restock-lists', RestockListController::class)->middleware('check.mobile');
            Route::post('/restock-lists/print', [RestockListController::class, 'print']);

            // Other Admin Routes
            Route::resource('/users', UserController::class);
            Route::post('/restocks/audit', [RestockController::class, 'audit']);

            Route::get('/restocks/excel-export', [RestockController::class, 'exportExcel']);
            Route::resource('/restocks', RestockController::class);

            Route::get('/stock-movements/export-excel', [StockMovementController::class, 'exportExcel']);
            Route::resource('/stock-movements', StockMovementController::class);
            Route::resource('/carts', CartController::class);
            Route::resource('/cart-items', CartItemController::class);
            Route::resource('/discount-products', DiscountProductController::class);

            Route::get('/transactions/export-excel', [TransactionController::class, 'exportExcel']);
            Route::resource('/transactions', TransactionController::class);

            Route::get('/transaction-items/export-excel', [TransactionItemController::class, 'exportExcel']);
            Route::resource('/transaction-items', TransactionItemController::class);

            Route::resource('/discounts', DiscountController::class);
            Route::resource('/customers', CustomerController::class);

            Route::get('/debt-items/export-excel', [DebtItemController::class, 'exportExcel']);
            Route::resource('/debt-items', DebtItemController::class);

            Route::get('/debts/export-excel', [DebtController::class, 'exportExcel']);
            Route::post('/debts/delete', [DebtController::class, 'destroy']);
            Route::resource('/debts', DebtController::class);

            // Supplier Routes
            Route::post('/suppliers/getByName', [SupplierController::class, 'getByName']);
            Route::resource('/suppliers', SupplierController::class);

            // Report Routes
            Route::get('/reports/transaction', [ReportController::class, 'transactionIndex']);
            Route::get('/reports/purchase', [ReportController::class, 'purchaseIndex']);

            // Debt Payment History
            Route::get('/debt-payments-history/export-excel', [DebtPaymentHistoryController::class, 'exportExcel']);
            Route::get('/debt-payment-history', [DebtPaymentHistoryController::class, 'index']);

            Route::resource('/store-settings', StoreSettingController::class);
        });
    });


    Route::middleware('role:admin|cashier')->group(function () {
        // POS Routes
        Route::post('/customers/getCustomer', [CustomerController::class, 'getCustomer']);
        Route::post('/products/getBySku', [ProductController::class, 'getProduct']);
        Route::post('/products/getByName', [ProductController::class, 'getProductByName']);
        Route::post('/products/getVariantByName', [ProductController::class, 'getProductVariantByName']);

        // Route::get('/pos', [PosController::class, 'index'])->name('pos');

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
            Route::get('/count-transaction', [PosController::class, 'countTransaction']);
        });
    });
    
    // Settings Routes
    Route::resource('/userSettings', UserSettingController::class);
    Route::resource('/globalSettings', SettingController::class);
    Route::post('/settings/storeSettings', [SettingController::class, 'updateStoreSettings']);
    Route::get('/settings/getSettings', [SettingController::class, 'getSettings']);
    Route::post('/settings/updateSettings', [SettingController::class, 'updateSettings']);
    Route::resource('/settings', SettingController::class);

    // Purchase Routes
    Route::resource('/purchases', PurchaseController::class);
});

Route::middleware(['auth', 'verified', 'role:super-admin'])->group(function () {
    Route::get('/super-admin/dashboard', fn() => Inertia::render('SuperAdmin/Dashboard'))->name('dashboard.superadmin');

    Route::get('/super-admin/store-applications/export-excel', [StoreApplicationController::class, 'exportExcel']);
    Route::resource('/super-admin/store-applications', StoreApplicationController::class);

    Route::get('/super-admin/stores/export-excel', [SuperStoreController::class, 'exportExcel']);
    Route::resource('/super-admin/stores', SuperStoreController::class);

    Route::get('/super-admin/ads/receipts', [AdsController::class, 'indexReceipt']);
    Route::post('/super-admin/ads/receipts', [AdsController::class, 'storeReceipt']);

    Route::resource('/super-admin/ads/slides', AdsController::class);
    Route::resource('/super-admin/users', UserController::class);
});


Route::post('/generate', [CartController::class, 'generateReceipt']);

Route::get('/test', function () {
    return Inertia::render('Test');
});
require __DIR__ . '/auth.php';
