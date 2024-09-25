<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Admin\CartController;
use App\Http\Controllers\Admin\DebtController;
use App\Http\Controllers\Admin\UnitController;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\Admin\StockController;
use App\Http\Controllers\Admin\ProductController;
use App\Http\Controllers\Admin\CartItemController;
use App\Http\Controllers\Admin\CategoryController;
use App\Http\Controllers\Admin\CustomerController;
use App\Http\Controllers\Admin\TransactionController;
use App\Http\Controllers\Admin\StockMovementController;
use App\Http\Controllers\Admin\TransactionItemController;

// Route::get('/user', function (Request $request) {
//     return $request->user();
// })->middleware('auth:sanctum');

Route::get('/users', [UserController::class, 'userData']);
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
Route::get('/transaction-items', [TransactionItemController::class, 'transactionItemData']);

Route::post('/products/getByName', [ProductController::class, 'getProductByName']);

Route::get('/customers', [CustomerController::class, 'customerData']);
// Route::get('/debt-items', [DebtItemController::class, 'debtItemData']);
Route::get('/units', [UnitController::class, 'unitData']);
