<?php
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\PurchaseController;
use App\Http\Controllers\Admin\CartController;
use App\Http\Controllers\Admin\UnitController;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\Admin\StockController;
use App\Http\Controllers\RestockListController;
use App\Http\Controllers\Admin\ReportController;
use App\Http\Controllers\Admin\ProductController;
use App\Http\Controllers\Admin\RestockController;
use App\Http\Controllers\Admin\CartItemController;
use App\Http\Controllers\Admin\CategoryController;
use App\Http\Controllers\Admin\CustomerController;
use App\Http\Controllers\Admin\DebtItemController;
use App\Http\Controllers\Admin\DiscountController;
use App\Http\Controllers\Admin\SupplierController;
use App\Http\Controllers\Admin\TransactionController;
use App\Http\Controllers\Admin\StockMovementController;
use App\Http\Controllers\Admin\DiscountProductController;
use App\Http\Controllers\Admin\TransactionItemController;
use App\Http\Controllers\Admin\DebtPaymentHistoryController;

// User Routes
Route::get('/users', [UserController::class, 'userData']);
Route::get('/users/chartData', [UserController::class, 'getUserChartData']);

// Category and Product Routes
Route::get('/categories', [CategoryController::class, 'categoryData']);
Route::get('/products', [ProductController::class, 'productData']);
Route::get('/productVariants', [ProductController::class, 'productVariantData']);
Route::post('/products/check-sku', [ProductController::class, 'checkSKU']);
Route::post('/products/get', [ProductController::class, 'getProduct']);
Route::post('/products/getVariantByName', [ProductController::class, 'getProductByName']);

// Stock Routes
Route::get('/restocks', [RestockController::class, 'restockData']);
Route::get('/stock-movements', [StockMovementController::class, 'stockMovementData']);

// Cart and Cart Item Routes
Route::get('/carts', [CartController::class, 'cartData']);
Route::post('/carts/addProducts', [CartController::class, 'addProducts']);
Route::get('/cart-items', [CartItemController::class, 'cartItemData']);

// Transaction Routes
Route::get('/transactions', [TransactionController::class, 'transactionData']);
Route::get('/transactions/chartData', [TransactionController::class, 'getTransactionChartData']);
Route::get('/transaction-items', [TransactionItemController::class, 'transactionItemData']);

// Customer and Debt Routes
Route::get('/customers', [CustomerController::class, 'customerData']);
Route::get('/debt-items', [DebtItemController::class, 'debtItemData']);
Route::get('/debt-payment-history', [DebtPaymentHistoryController::class, 'getDebtPaymentHistory']);

// Unit Routes
Route::get('/units', [UnitController::class, 'unitData']);

// Supplier and Purchase Routes
Route::get('/suppliers', [SupplierController::class, 'supplierData']);
Route::get('/purchases', [PurchaseController::class, 'purchaseData']);
Route::get('/purchases/chartData', [PurchaseController::class, 'getPurchaseChartData']);
Route::get('restock-lists', [RestockListController::class, 'restockListData']);

// Report Routes
Route::get('/transactions/{year}/{month}', [ReportController::class, 'getMonthlytransaction']);
Route::get('/purchases/{year}/{month}', [ReportController::class, 'getMonthlypurchase']);

// Discount Routes
Route::get('/discounts', [DiscountController::class, 'discountData']);
Route::get('/discount-products', [DiscountProductController::class, 'discountProductData']);
