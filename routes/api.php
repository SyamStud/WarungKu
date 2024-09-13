<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\Admin\ProductController;
use App\Http\Controllers\Admin\CategoryController;

// Route::get('/user', function (Request $request) {
//     return $request->user();
// })->middleware('auth:sanctum');

Route::get('/users', [UserController::class, 'userData']);
Route::get('/categories', [CategoryController::class, 'categoryData']);
Route::get('/products', [ProductController::class, 'productData']);
Route::post('/products/check-sku', [ProductController::class, 'checkSKU']);