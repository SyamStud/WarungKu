<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\RestockController;
use App\Http\Controllers\Admin\CartController;
use App\Http\Controllers\Admin\UnitController;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\Admin\StockController;
use App\Http\Controllers\RestockListController;
use App\Http\Controllers\Admin\ReportController;
use App\Http\Controllers\Admin\ProductController;
use App\Http\Controllers\Admin\CartItemController;
use App\Http\Controllers\Admin\CategoryController;
use App\Http\Controllers\Admin\CustomerController;
use App\Http\Controllers\Admin\DebtItemController;
use App\Http\Controllers\Admin\DiscountController;
use App\Http\Controllers\Admin\SupplierController;
use App\Http\Controllers\Admin\TransactionController;
use App\Http\Controllers\Admin\StockMovementController;
use App\Http\Controllers\Admin\TransactionItemController;
use App\Http\Controllers\Admin\DebtPaymentHistoryController;
use App\Http\Controllers\Admin\DiscountProductController;

Route::get('/users', [UserController::class, 'userData']);
Route::get('/users/chartData', [UserController::class, 'getUserChartData']);

Route::get('/categories', [CategoryController::class, 'categoryData']);
Route::get('/products', [ProductController::class, 'productData']);
Route::get('/productVariants', [ProductController::class, 'productVariantData']);
Route::post('/products/check-sku', [ProductController::class, 'checkSKU']);
Route::post('/products/get', [ProductController::class, 'getProduct']);

Route::get('/stocks', [StockController::class, 'stockData']);
Route::get('/stock-movements', [StockMovementController::class, 'stockMovementData']);
Route::get('/carts', [CartController::class, 'cartData']);
Route::post('/carts/addProducts', [CartController::class, 'addProducts']);

Route::get('/cart-items', [CartItemController::class, 'cartItemData']);
Route::get('/transactions', [TransactionController::class, 'transactionData']);
Route::get('/transactions/chartData', [TransactionController::class, 'getTransactionChartData']);
Route::get('/transaction-items', [TransactionItemController::class, 'transactionItemData']);

Route::post('/products/getVariantByName', [ProductController::class, 'getProductByName']);

Route::get('/customers', [CustomerController::class, 'customerData']);
Route::get('/debt-items', [DebtItemController::class, 'debtItemData']);
Route::get('/units', [UnitController::class, 'unitData']);

Route::get('/suppliers', [SupplierController::class, 'supplierData']);
Route::get('/restocks', [RestockController::class, 'restockData']);
Route::get('/restocks/chartData', [RestockController::class, 'getRestockChartData']);

Route::get('restock-lists', [RestockListController::class, 'restockListData']);

Route::get('/debt-payment-history', [DebtPaymentHistoryController::class, 'getDebtPaymentHistory']);

Route::get('/transactions/{year}/{month}', [ReportController::class, 'getMonthlytransaction']);
Route::get('/purchases/{year}/{month}', [ReportController::class, 'getMonthlypurchase']);

Route::get('/discounts', [DiscountController::class, 'discountData']);
Route::get('/discount-products', [DiscountProductController::class, 'discountProductData']);
