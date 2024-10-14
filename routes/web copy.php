<?php

use Inertia\Inertia;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Pos\PosController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\RestockController;
use App\Http\Controllers\SettingController;
use App\Http\Controllers\Admin\CartController;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\Admin\StockController;
use App\Http\Controllers\RestockListController;
use App\Http\Controllers\UserSettingController;
use App\Http\Controllers\Admin\ReportController;
use App\Http\Controllers\Admin\ProductController;
use App\Http\Controllers\Admin\CartItemController;
use App\Http\Controllers\Admin\CategoryController;
use App\Http\Controllers\Admin\CustomerController;
use App\Http\Controllers\Admin\DebtItemController;
use App\Http\Controllers\Admin\DiscountController;
use App\Http\Controllers\Admin\SupplierController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Pos\DebtPaymentController;
use App\Http\Controllers\Admin\TransactionController;
use App\Http\Controllers\Admin\StockMovementController;
use Illuminate\Foundation\Auth\EmailVerificationRequest;
use App\Http\Controllers\Admin\DiscountProductController;
use App\Http\Controllers\Admin\TransactionItemController;
use App\Http\Controllers\Admin\DebtPaymentHistoryController;

Route::get('/email/verify', function () {
    return view('auth.verify-email');
})->middleware('auth')->name('verification.notice');

Route::get('/email/verify/{id}/{hash}', function (EmailVerificationRequest $request) {
    $request->fulfill();

    return redirect('/home');
})->middleware(['auth', 'signed'])->name('verification.verify');

Route::post('/email/verification-notification', function (Request $request) {
    $request->user()->sendEmailVerificationNotification();

    return back()->with('message', 'Verification link sent!');
})->middleware(['auth', 'throttle:6,1'])->name('verification.send');


Route::get('/', function () {
    return Inertia::render('Auth/Login');
});

Route::get('/dashboard', function () {
    return Inertia::render('Admin/Dashboard');
})->middleware(['auth', 'verified'])->name('dashboard');

Route::get('/not-available-mobile', function () {
    return Inertia::render('NotAvailableMobile');
})->name('not-available-mobile');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

Route::middleware('auth')->group(function () {
    Route::get('/admin/dashboard', [DashboardController::class, 'index'])->name('admin.dashboard');
    Route::get('/admin/dashboard/dashboardSummary', [DashboardController::class, 'dashboardSummary']);

    Route::get('/admin/products/add', [ProductController::class, 'add'])->name('products.add');
    Route::get('/admin/products/add-variant', [ProductController::class, 'addVariant'])->name('products.add.variant');
    Route::post('/admin/products/add-variant', [ProductController::class, 'storeVariant'])->name('products.store.variant');

    Route::resource('/admin/restocks', RestockController::class);
    Route::resource('/admin/restock-lists', RestockListController::class)->middleware('check.mobile');
    Route::post('/admin/restock-lists/print', [RestockListController::class, 'print']);

    Route::resource('/admin/users', UserController::class);
    Route::resource('/admin/categories', CategoryController::class);
    Route::resource('/admin/products', ProductController::class);
    Route::resource('/admin/stocks', StockController::class);
    Route::resource('/admin/stock-movements', StockMovementController::class);
    Route::resource('/admin/carts', CartController::class);
    Route::resource('/admin/cart-items', CartItemController::class);
    Route::resource('/admin/discount-products', DiscountProductController::class);

    Route::resource('/admin/transactions', TransactionController::class);

    Route::resource('/admin/discounts', DiscountController::class);

    Route::resource('/admin/transaction-items', TransactionItemController::class);
    Route::resource('/admin/customers', CustomerController::class);
    Route::resource('/admin/debt-items', DebtItemController::class);

    Route::get('/admin/reports/transaction', [ReportController::class, 'transactionIndex']);
    Route::get('/admin/reports/purchase', [ReportController::class, 'purchaseIndex']);

    Route::post('/suppliers/getByName', [SupplierController::class, 'getByName']);
    Route::resource('/admin/suppliers', SupplierController::class);

    Route::get('/admin/debt-payment-history', [DebtPaymentHistoryController::class, 'index']);

    Route::get('/admin/debts', [DebtItemController::class, 'debtIndex']);

    // POS
    Route::post('/customers/getCustomer', [CustomerController::class, 'getCustomer']);
    Route::get('/pos/debt-payments', [DebtPaymentController::class, 'index']);
    Route::post('/pos/debt-payments/store-payment', [DebtPaymentController::class, 'storePayment']);


    Route::post('/products/getBySku', [ProductController::class, 'getProduct']);
    Route::post('/products/getByName', [ProductController::class, 'getProductByName']);
    Route::post('/products/getVariantByName', [ProductController::class, 'getProductVariantByName']);
    Route::resource('/pos', PosController::class);
    Route::get('/pos/carts/getUserCart', [CartController::class, 'getUserCart']);
    Route::post('/pos/carts/addProduct', [CartController::class, 'addProduct']);
    Route::post('/pos/carts/addItem', [CartController::class, 'addItem']);
    Route::post('/pos/carts/updateProduct', [CartController::class, 'updateProduct']);
    Route::post('/pos/carts/removeProduct', [CartController::class, 'removeProduct']);
    Route::post('/pos/carts/revoke', [CartController::class, 'revoke']);
    Route::post('/pos/carts/updateVariant', [CartController::class, 'updateVariant']);
    Route::post('/pos/carts/store-transaction', [CartController::class, 'storeTransaction']);


    //
    Route::resource('/userSettings', UserSettingController::class);
    Route::resource('/globalSettings', SettingController::class);
    Route::get('/settings/getSettings', [SettingController::class, 'getSettings']);
    Route::post('/settings/updateSettings', [SettingController::class, 'updateSettings']);
    Route::resource('/settings', SettingController::class);
    Route::resource('/restocks', RestockController::class);
});

Route::get('/print', [CartController::class, 'print']);
Route::post('/test-report', [ReportController::class, 'savetransactionReport']);

require __DIR__ . '/auth.php';
