<?php

namespace App\Http\Controllers\Admin;

use App\Models\Ads;
use App\Models\Cart;
use App\Models\Debt;
use Inertia\Inertia;
use App\Models\Product;
use App\Models\Restock;
use App\Models\Setting;
use App\Models\CartItem;
use App\Models\DebtItem;
use App\Models\Discount;
use Mike42\Escpos\Printer;
use App\Models\Transaction;
use App\Models\StoreSetting;
use Illuminate\Http\Request;
use App\Models\StockMovement;
use App\Models\ProductVariant;
use App\Models\DiscountProduct;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Mike42\Escpos\CapabilityProfile;
use Illuminate\Support\Facades\Cache;
use Barryvdh\Debugbar\Facades\Debugbar;
use Illuminate\Support\Facades\Validator;
use Mike42\Escpos\PrintConnectors\WindowsPrintConnector;

class CartController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        return Inertia::render('Admin/Cart');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Cart $cart)
    {
        $cart->delete();

        return response()->json([
            'message' => 'Cart deleted successfully',
            'status' => 'success',
        ]);
    }

    /**
     * Get all carts data.
     */
    public function cartData(Request $request)
    {
        $query = Cart::query()->where('store_id', Auth::user()->store->id)->with('user');

        // Handle global search
        if ($request->has('search')) {
            $searchTerm = $request->search;
            $query->where(function ($q) use ($searchTerm) {
                $q->where('transaction_code', 'like', "%{$searchTerm}%")
                    ->orWhere('total_price', 'like', "%{$searchTerm}%")
                    ->orWhereHas('user', function ($q) use ($searchTerm) {
                        $q->where('name', 'like', "%{$searchTerm}%");
                    });
            });
        }

        // Handle sorting
        if ($request->has('sort')) {
            $sortField = $request->sort;
            $sortDirection = $request->input('direction', 'asc');
            $query->orderBy($sortField, $sortDirection);
        }

        // Handle pagination
        $perPage = $request->input('per_page', 10);
        $carts = $query->paginate($perPage);


        // Calculate 'from' and 'to'
        $from = ($carts->currentPage() - 1) * $carts->perPage() + 1;
        $to = min($from + $carts->count() - 1, $carts->total());

        $transformedCarts = $carts->map(function ($cart) {
            return [
                'id' => $cart->id,
                'transaction_code' => $cart->transaction_code,
                'total_price' => $cart->total_price,
                'discount' => $cart->discount,
                'tax' => $cart->tax,
                'grand_total' => $cart->grand_total,
                'user' => $cart->user_id ? $cart->user->name : '-',
                'created_at' => $cart->created_at->format('d F Y H:i'),
            ];
        });

        return response()->json([
            'data' => $transformedCarts,
            'meta' => [
                'current_page' => $carts->currentPage(),
                'last_page' => $carts->lastPage(),
                'per_page' => $carts->perPage(),
                'total' => $carts->total(),
                'from' => $from,
                'to' => $to,
            ],
        ]);
    }

    public function getUserCart()
    {
        $userId = Auth::id();

        $cart = Cart::where('user_id', $userId)->where('store_id', Auth::user()->store->id)->first();

        if (!$cart) {
            return response()->json([
                'message' => 'Cart is empty',
                'status' => 'success',
                'data' => [],
            ]);
        }

        $cartItems = CartItem::with(['product', 'productVariant.unit'])
            ->where('cart_id', $cart->id)
            ->get()
            ->map(function ($cartItem) {
                $product = $cartItem->product;
                $variants = $product->productVariants->map(function ($variant) {
                    return [
                        'id' => $variant->id,
                        'variant' => $variant->quantity == 1
                            ? $variant->unit->name
                            : $variant->quantity . $variant->unit->name,
                        'price' => $variant->price
                    ];
                });

                return [
                    'id' => $cartItem->id,
                    'product_id' => $cartItem->product_id,
                    'quantity' => $cartItem->quantity,
                    'price' => $cartItem->price,
                    'total_price' => $cartItem->total_price,
                    'product_variant_id' => $cartItem->product_variant_id,
                    'sku' => $product->sku,
                    'name' => $product->name,
                    'variant_id' => (string) $cartItem->product_variant_id,
                    'product_variants' => $variants,
                    'discounted_price' => $cartItem->discounted_price,
                    'discount' => $cartItem->discount,
                    'discounted_total_price' => $cartItem->discounted_total_price,
                ];
            });

        return response()->json([
            'message' => 'Cart items retrieved successfully',
            'status' => 'success',
            'data' => $cartItems,
            'grand_total' => $cart->grand_total,
            'transaction_code' => $cart->transaction_code,
            'cart' => $cart,
        ]);
    }

    public function updateProduct(Request $request)
    {
        $request->validate([
            'id' => 'required|integer',
            'quantity' => 'required|integer',
            'transaction_code' => 'required|string',
        ]);

        $cartItem = CartItem::find($request->id);

        if (!$cartItem) {
            return response()->json([
                'message' => 'Cart item not found',
                'status' => 'error',
            ], 404);
        }

        if ($cartItem->productVariant->stock < $request->quantity) {

            $cart = Cart::find($cartItem->cart_id);

            return response()->json([
                'message' => 'Stok tidak mencukupi',
                'status' => 'error',
                'grand_total' => $cart->grand_total,
                'data' => $this->getUserCart(),
                'cart' => $cart,
            ]);
        }

        $productDiscount = $this->getProductDiscount($cartItem->product_variant_id, $request->quantity);

        $cartItem->discounted_price = $productDiscount
            ? $this->calculateProductDiscount($cartItem->price, $productDiscount)
            : $cartItem->price;
        $cartItem->save();

        $cartItem->update([
            'quantity' => $request->quantity,
            'total_price' => $cartItem->price * $request->quantity,
            'discounted_total_price' => $cartItem->discounted_price * $request->quantity,
        ]);

        $productDiscountAmount = 0;
        if ($productDiscount) {
            if ($productDiscount['discount']['amount_type'] == 'percentage') {
                $productDiscountAmount = $cartItem->total_price * ($productDiscount['discount']['amount'] / 100);
            } else {
                $productDiscountAmount =  $productDiscount['discount']['amount'] * $cartItem->quantity;
            }
        }

        $cartItem->discount = $productDiscount ? $productDiscountAmount : 0;
        $cartItem->save();

        $cart = Cart::find($cartItem->cart_id);

        $totalPrice = $cart->cartItems()->sum('total_price');
        $totalDiscount = $cart->cartItems()->sum('discount');

        $orderDiscount = $this->getOrderDiscount(($totalPrice - $totalDiscount));

        $orderDiscountAmount = 0;
        if ($orderDiscount) {
            $orderDiscountAmount = $orderDiscount['amount_type'] == 'percentage'
                ? ($totalPrice * ($orderDiscount['amount'] / 100))
                : $orderDiscount['amount'];

            $totalDiscount += $orderDiscountAmount;
        }

        $grandTotal = $totalPrice - $totalDiscount;
        $grandTotal += $this->calculateTax($grandTotal);

        $cart->total_price = $totalPrice;
        $cart->discount = $totalDiscount;
        $cart->grand_total = $grandTotal;
        $cart->tax = $this->calculateTax($totalPrice - $totalDiscount);
        $cart->save();

        return response()->json([
            'message' => 'Product quantity updated successfully',
            'status' => 'success',
            'grand_total' => $cart->grand_total,
            'data' => $this->getUserCart(),
            'cart' => $cart,
        ]);
    }

    /**
     * Remove product from cart.
     */
    public function removeProduct(Request $request)
    {
        $request->validate([
            'id' => 'required|integer',
        ]);

        $cartItem = CartItem::find($request->id);

        if (!$cartItem) {
            return response()->json([
                'message' => 'Cart item not found',
                'status' => 'error',
            ], 404);
        }

        $cartItem->delete();

        return response()->json([
            'message' => 'Product removed from cart successfully',
            'status' => 'success',
        ]);
    }

    public function revoke(Request $request)
    {
        $request->validate([
            'transaction_code' => 'required|string',
        ]);

        $cart = Cart::where('user_id', Auth::id())->where('store_id', Auth::user()->store->id)->where('transaction_code', $request->transaction_code)->first();

        if (!$cart) {
            return response()->json([
                'message' => 'Cart not found',
                'status' => 'error',
            ], 404);
        }

        $cart->delete();

        return response()->json([
            'message' => 'Cart revoked successfully',
            'status' => 'success',
        ]);
    }

    public function updateVariant(Request $request)
    {
        $request->validate([
            'id' => 'required|integer',
            'variant_id' => 'required|integer',
            'transaction_code' => 'required|string',
        ]);

        $cartItem = CartItem::find($request->id);

        if (!$cartItem) {
            return response()->json([
                'message' => 'Cart item not found',
                'status' => 'error',
            ], 404);
        }

        $newVariant = $cartItem->product->productVariants->find($request->variant_id);

        if (!$newVariant) {
            return response()->json([
                'message' => 'Product variant not found',
                'status' => 'error',
            ], 404);
        }

        // Check if there's already a cart item with the same product and new variant
        $existingItem = CartItem::where('cart_id', $cartItem->cart_id)
            ->where('product_id', $cartItem->product_id)
            ->where('product_variant_id', $request->variant_id)
            ->where('id', '!=', $cartItem->id)
            ->where('store_id', Auth::user()->store_id)
            ->first();

        if ($existingItem) {
            if ($existingItem->productVariant->stock < $cartItem->quantity) {
                return response()->json([
                    'message' => 'Stok tidak mencukupi',
                    'status' => 'error',
                ]);
            }

            $existingItem->quantity += $cartItem->quantity;

            $productDiscount = $this->getProductDiscount($request->variant_id, $existingItem->quantity);
            $productDiscountAmount = 0;

            if ($productDiscount) {
                if ($productDiscount['discount']['amount_type'] == 'percentage') {
                    $productDiscountAmount = ($newVariant->price * $cartItem->quantity) * ($productDiscount['discount']['amount'] / 100);
                } else {
                    $productDiscountAmount =  $existingItem->quantity * $productDiscount['discount']['amount'];
                }
            }

            $existingItem->total_price = $existingItem->quantity * $newVariant->price;
            $existingItem->discount = $productDiscount ? $productDiscountAmount : 0;
            $existingItem->discounted_price = $productDiscount ? $this->calculateProductDiscount($newVariant->price, $productDiscount) : $newVariant->price;
            $existingItem->discounted_total_price = $productDiscount ? $existingItem->quantity * $this->calculateProductDiscount($newVariant->price, $productDiscount) : $existingItem->quantity * $newVariant->price;
            $existingItem->save();

            $cartItem->delete();
        } else {
            if ($newVariant->stock < $cartItem->quantity) {
                return response()->json([
                    'message' => 'Stok tidak mencukupi',
                    'status' => 'error',
                ]);
            }

            $productDiscount = $this->getProductDiscount($request->variant_id, $cartItem->quantity);
            $productDiscountAmount = 0;

            if ($productDiscount) {
                if ($productDiscount['discount']['amount_type'] == 'percentage') {
                    $productDiscountAmount = ($newVariant->price * $cartItem->quantity) * ($productDiscount['discount']['amount'] / 100);
                } else {
                    $productDiscountAmount =  $cartItem->quantity * $productDiscount['discount']['amount'];
                }
            }

            $cartItem->product_variant_id = $request->variant_id;
            $cartItem->price = $newVariant->price;
            $cartItem->total_price = $newVariant->price * $cartItem->quantity;
            $cartItem->discount = $productDiscount ? $productDiscountAmount : 0;
            $cartItem->discounted_price = $productDiscount ? $this->calculateProductDiscount($newVariant->price, $productDiscount) : $newVariant->price;
            $cartItem->discounted_total_price = $productDiscount ? $cartItem->quantity * $this->calculateProductDiscount($newVariant->price, $productDiscount) : $cartItem->quantity * $newVariant->price;
            $cartItem->save();
        }

        $cart = Cart::find($cartItem->cart_id);
        $totalPrice = $cart->cartItems()->sum('total_price');
        $discount = $cart->cartItems()->sum('discount');

        $orderDiscount = $this->getOrderDiscount(($totalPrice - $discount));

        $orderDiscountAmount = 0;
        if ($orderDiscount) {
            $orderDiscountAmount = $orderDiscount['amount_type'] == 'percentage'
                ? ($totalPrice * ($orderDiscount['amount'] / 100))
                : $orderDiscount['amount'];

            $discount += $orderDiscountAmount;
        }

        $grandTotal = $totalPrice - $discount;
        $grandTotal += $this->calculateTax($grandTotal);

        $cart->update([
            'total_price' => $totalPrice,
            'discount' => $discount,
            'grand_total' => $grandTotal,
            'tax' => $this->calculateTax($totalPrice - $discount),
        ]);

        $transformedCartItems = $cart->cartItems->map(function ($cartItem) {
            return [
                'sku' => $cartItem->product->sku,
                'id' => $cartItem->id,
                'product_id' => $cartItem->product_id,
                'name' => $cartItem->product->name,
                'quantity' => $cartItem->quantity,
                'price' => $cartItem->price,
                'discount' => $cartItem->discount,
                'discounted_price' => $cartItem->discounted_price,
                'total_price' => $cartItem->total_price,
                'discounted_total_price' => $cartItem->discounted_total_price,
                'variant_id' => (string) $cartItem->product_variant_id,
                'product_variants' => $cartItem->productVariant->product->productVariants->map(function ($productVariant) {
                    return [
                        'id' => $productVariant->id,
                        'variant' => $productVariant->quantity == '1' ? $productVariant->unit->name : $productVariant->quantity . $productVariant->unit->name,
                        'price' => $productVariant->price,
                    ];
                }),
            ];
        });

        return response()->json([
            'message' => 'Product variant updated successfully',
            'status' => 'success',
            'data' => $transformedCartItems,
            'grand_total' => $cart->grand_total,
            'cart' => $cart,
        ]);
    }

    public function addItem(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'identifier' => 'required|string',
            'transaction_code' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => $validator->errors()->first(),
                'status' => 'error',
            ], 400);
        }

        // $cacheKey = 'product_' . md5($request->identifier);

        $product = DB::table('products')
            ->select('products.id', 'products.sku', 'products.name', 'product_variants.id as variant_id', 'product_variants.price', 'product_variants.status', 'product_variants.quantity', 'units.name as unit_name')
            ->join('product_variants', 'products.id', '=', 'product_variants.product_id')
            ->join('units', 'product_variants.unit_id', '=', 'units.id')
            ->where('products.sku', $request->identifier)
            ->where('product_variants.status', 'active')
            ->where('products.store_id', Auth::user()->store_id)
            ->first();

        // $product = Cache::remember($cacheKey, now()->addHours(24), function () use ($request) {
        //     // Use query builder for faster lookup
        //     return DB::table('products')
        //         ->select('products.id', 'products.sku', 'products.name', 'product_variants.id as variant_id', 'product_variants.price', 'product_variants.status', 'product_variants.quantity', 'units.name as unit_name')
        //         ->join('product_variants', 'products.id', '=', 'product_variants.product_id')
        //         ->join('units', 'product_variants.unit_id', '=', 'units.id')
        //         ->where('products.sku', $request->identifier)->first();
        // });

        if (!$product) {
            $products = Product::select(
                'products.id',
                'products.sku',
                'products.name',
                'product_variants.id as variant_id',
                'product_variants.price',
                'product_variants.status',
                'product_variants.quantity',
                'units.name as unit_name'
            )
                ->join('product_variants', 'products.id', '=', 'product_variants.product_id')
                ->join('units', 'product_variants.unit_id', '=', 'units.id')
                ->where('products.name', 'like', '%' . $request->identifier . '%')
                ->where('product_variants.status', 'active')
                ->where('products.store_id', Auth::user()->store_id)
                ->whereNull('product_variants.deleted_at') // Abaikan varian yang soft delete
                ->get();



            // $products = Cache::remember($cacheKey . '_name', now()->addHours(24), function () use ($request) {
            //     return DB::table('products')
            //         ->select('products.id', 'products.sku', 'products.name', 'product_variants.id as variant_id', 'product_variants.price', 'product_variants.status', 'product_variants.quantity', 'units.name as unit_name')
            //         ->join('product_variants', 'products.id', '=', 'product_variants.product_id')
            //         ->join('units', 'product_variants.unit_id', '=', 'units.id')
            //         ->where('products.name', 'like', '%' . $request->identifier . '%')
            //         ->get();
            // });

            $transformedProducts = $products->map(function ($product) use ($request) {
                return [
                    'identifier' => $product->sku,
                    'transaction_code' => $request->transaction_code,
                    'id' => $product->id,
                    'variant_id' => $product->variant_id,
                    'sku' => $product->sku,
                    'name' => $product->name,
                    'price' => $product->price,
                    'variant' => $product->quantity == '1' ? $product->unit_name : $product->quantity . ' ' . $product->unit_name,
                ];
            });

            return response()->json([
                'product' => $transformedProducts,
            ]);
        }

        if ($request->variant_id != 0) {
            $product = DB::table('products')
                ->select('products.id', 'products.sku', 'products.name', 'product_variants.id as variant_id', 'product_variants.price', 'product_variants.status', 'product_variants.quantity', 'product_variants.stock', 'units.name as unit_name')
                ->join('product_variants', 'products.id', '=', 'product_variants.product_id')
                ->join('units', 'product_variants.unit_id', '=', 'units.id')
                ->where('products.sku', $request->identifier)
                ->where('product_variants.id', $request->variant_id)
                ->where('products.store_id', Auth::user()->store_id)
                ->first();
        }

        if ($product->stock < 1) {
            return response()->json([
                'message' => 'Stok tidak mencukupi',
                'status' => 'error',
            ]);
        }

        return DB::transaction(function () use ($request, $product) {
            $cart = DB::table('carts')->where('user_id', Auth::id())
                ->where('transaction_code', $request->transaction_code)
                ->where('store_id', Auth::user()->store_id)
                ->first();

            if (!$cart) {
                $cartId = DB::table('carts')->insertGetId([
                    'user_id' => Auth::id(),
                    'transaction_code' => $request->transaction_code,
                    'total_price' => 0,
                    'discount' => 0,
                    'tax' => 0,
                    'grand_total' => 0,
                    'store_id' => Auth::user()->store_id,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            } else {
                $cartId = $cart->id;
            }

            $cartItem = DB::table('cart_items')
                ->where('cart_id', $cartId)
                ->where('product_variant_id', $product->variant_id)
                ->where('store_id', Auth::user()->store_id)
                ->first();

            if ($cartItem) {
                if ($product->stock < $cartItem->quantity + 1) {
                    return response()->json([
                        'message' => 'Stok tidak mencukupi',
                        'status' => 'error',
                    ]);
                }

                DB::table('cart_items')
                    ->where('id', $cartItem->id)
                    ->update([
                        'quantity' => DB::raw('quantity + 1'),
                        'total_price' => DB::raw('price * (quantity)'),
                        'updated_at' => now(),
                    ]);

                $cartItem = CartItem::where('cart_id', $cartId)
                    ->where('product_variant_id', $product->variant_id)
                    ->where('store_id', Auth::user()->store_id)
                    ->first();

                $productDiscount = $this->getProductDiscount($product->variant_id, $cartItem->quantity);

                if ($productDiscount) {
                    $cartItem->discounted_price = $this->calculateProductDiscount($product->price, $productDiscount);
                    $cartItem->discounted_total_price = $cartItem->discounted_price * $cartItem->quantity;

                    if ($productDiscount['discount']['amount_type'] == 'percentage') {
                        $cartItem->discount = $cartItem->total_price * ($productDiscount['discount']['amount'] / 100);
                    } else {
                        $cartItem->discount =  $productDiscount['discount']['amount'] * $cartItem->quantity;
                    }

                    $cartItem->save();
                } else {
                    $cartItem->discounted_price = null;
                    $cartItem->discounted_total_price = null;
                    $cartItem->discount = 0;
                    $cartItem->save();
                }
            } else {
                $productDiscount = $this->getProductDiscount($product->variant_id, 1);

                $discounted_price = $productDiscount
                    ? $this->calculateProductDiscount($product->price, $productDiscount)
                    : null;

                $productDiscountAmount = 0;

                if ($productDiscount) {
                    if ($productDiscount['discount']['amount_type'] == 'percentage') {
                        $productDiscountAmount = $product->price * ($productDiscount['discount']['amount'] / 100);
                    } else {
                        $productDiscountAmount =  $productDiscount['discount']['amount'];
                    }
                }

                $cartItem = CartItem::create([
                    'cart_id' => $cartId,
                    'product_id' => $product->id,
                    'product_variant_id' => $product->variant_id,
                    'quantity' => 1,
                    'price' => $product->price,
                    'discount' => $productDiscount ? $productDiscountAmount : 0,
                    'discounted_price' => $discounted_price ? $discounted_price : $product->price,
                    'total_price' => $product->price,
                    'discounted_total_price' => $discounted_price ? $discounted_price : $product->price,
                    'store_id' => Auth::user()->store_id,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }

            $totalPrice = DB::table('cart_items')
                ->where('cart_id', $cartId)
                ->where('store_id', Auth::user()->store_id)
                ->sum('total_price');

            $totalDiscount = DB::table('cart_items')
                ->where('cart_id', $cartId)
                ->where('store_id', Auth::user()->store_id)
                ->sum('discount');

            $orderDiscount = $this->getOrderDiscount(($totalPrice - $totalDiscount));

            $orderDiscountAmount = 0;
            if ($orderDiscount) {
                $orderDiscountAmount = $orderDiscount['amount_type'] == 'percentage'
                    ? ($totalPrice * ($orderDiscount['amount'] / 100))
                    : $orderDiscount['amount'];

                $totalDiscount += $orderDiscountAmount;
            }

            $grandTotal = $totalPrice - $totalDiscount;
            $grandTotal += $this->calculateTax($grandTotal);

            DB::table('carts')
                ->where('id', $cartId)
                ->update([
                    'total_price' => $totalPrice,
                    'discount' => $totalDiscount,
                    'grand_total' => $grandTotal,
                    'tax' => $this->calculateTax($totalPrice - $totalDiscount),
                    'updated_at' => now()
                ]);

            return $this->getUserCart();
        });
    }

    public function storeTransaction(Request $request)
    {
        $request->validate([
            'transaction_code' => 'required|string',
            'total_payment' => 'required|numeric',
            'payment_method' => 'required|string|in:cash,qris,debt',
        ]);

        // VALIDASI CART
        $cart = Cart::where('user_id', Auth::id())
            ->where('transaction_code', $request->transaction_code)
            ->where('store_id', Auth::user()->store_id)
            ->first();

        if (!$cart) {
            return response()->json([
                'message' => 'Cart not found',
                'status' => 'error',
            ], 404);
        }

        // VALIDASI CART ITEM
        $cartItems = CartItem::where('cart_id', $cart->id)->where('store_id', Auth::user()->store_id)->with('product')->get();

        if ($cartItems->isEmpty()) {
            return response()->json([
                'message' => 'Cart is empty',
                'status' => 'error',
            ], 400);
        }

        // PENGURANGAN STOCK
        $stockMovement = $this->decreaseStock($cartItems);

        $transactionData = [
            'cart' => $cart,
            'cartItems' => $cartItems,
            'transaction_code' => $request->transaction_code,
            'total_payment' => $request->total_payment,
            'payment_method' => $request->payment_method,
        ];

        // $this->print($cart, $cartItems, $request->transaction_code, $request->total_payment, $request->payment_method);
        // try {

        return DB::transaction(function () use ($cart, $cartItems, $request, $transactionData) {

            // CREATE TRANSACTION
            $transaction = Transaction::create([
                'user_id' => Auth::id(),
                'transaction_code' => $cart->transaction_code,
                'total_price' => $cart->total_price,
                'discount' => $cart->discount,
                'tax' => $cart->tax,
                'grand_total' => $cart->grand_total,
                'total_payment' => $request->total_payment,
                'total_change' => $request->total_payment - ($cart->grand_total),
                'payment_method' => $request->payment_method,
                'total_profit' => 0,
                'store_id' => Auth::user()->store_id,
                'created_at' => now(),
                'updated_at' => now(),
            ]);


            // CREATE TRANSACTION ITEM
            foreach ($cartItems as $cartItem) {
                $remainingQuantity = $cartItem->quantity;
                $totalProfit = 0;

                while ($remainingQuantity > 0) {

                    // MENCARI STOCK TERSEDIA
                    $stockUsed = Restock::where('product_variant_id', $cartItem->product_variant_id)
                        ->where('quantity', '>', 0)
                        ->where('status', '!=', 'sold-out')
                        ->where('store_id', Auth::user()->store_id)
                        ->orderBy('created_at', 'asc')
                        ->first();

                    // KETIKA TIDAK ADA STOCK TERSEDIA
                    if (!$stockUsed) {

                        // MENCARI STOCK TERBARU
                        $stockUsed = Restock::where('product_variant_id', $cartItem->product_variant_id)
                            ->where('store_id', Auth::user()->store_id)
                            ->orderBy('created_at', 'desc')
                            ->first();

                        // MENCARI STOCK OVERDRAWN YANG DIBUAT HARI INI
                        $existingStock = Restock::where('product_variant_id', $cartItem->product_variant_id)
                            ->where('cost', 0)
                            ->whereDate('created_at', now()->toDateString())
                            ->where('store_id', Auth::user()->store_id)
                            ->first();

                        // JIKA SUDAH ADA STOCK OVERDRAWN YANG DIBUAT HARI INI
                        if ($existingStock) {
                            $stockUsed->difference += $remainingQuantity;
                            $stockUsed->status = 'overdrawn';
                            $stockUsed->save();
                        } else {
                            $stockUsed = Restock::create([
                                'product_variant_id' => $cartItem->product_variant_id,
                                'quantity' => 0,
                                'difference' => $remainingQuantity,
                                'cost' => 0,
                                'status' => 'overdrawn',
                                'store_id' => Auth::user()->store_id,
                            ]);
                        }

                        $profit = 0;
                        $totalProfit += $profit;

                        $transactionItem = DB::table('transaction_items')->insertGetId([
                            'transaction_id' => $transaction->id,
                            'product_id' => $cartItem->product_id,
                            'product_variant_id' => $cartItem->product_variant_id,
                            'quantity' => $remainingQuantity,
                            'price' => $cartItem->price,
                            'discount' => $cartItem->discount,
                            'discounted_price' => $cartItem->discounted_price,
                            'total_price' => $cartItem->price * $remainingQuantity,
                            'discounted_total_price' => $cartItem->discounted_price * $remainingQuantity,
                            'profit' => $profit,
                            'restock_id' => $stockUsed->id,
                            'store_id' => Auth::user()->store_id,
                            'created_at' => now(),
                            'updated_at' => now(),
                        ]);

                        if ($request->payment_method == 'debt') {
                            $debt = Debt::where('transaction_id', $transaction->id)->where('store_id', Auth::user()->store_id)->first();
                            if (!$debt) {
                                $debt = Debt::create([
                                    'customer_id' => $request->customer_id,
                                    'transaction_id' => $transaction->id,
                                    'total_amount' => $cart->grand_total,
                                    'remaining_amount' => $cart->grand_total,
                                    'store_id' => Auth::user()->store_id,
                                ]);
                            }

                            $quantityFromThisStock = min($remainingQuantity, $stockUsed->difference);

                            DebtItem::create([
                                'debt_id' => $debt->id,
                                'transaction_item_id' => $transactionItem,
                                'total_amount' => $cartItem->discounted_price * $quantityFromThisStock,
                                'remaining_amount' => $cartItem->discounted_price * $quantityFromThisStock,
                                'store_id' => Auth::user()->store_id,
                            ]);
                        }

                        break;
                    }

                    $quantityFromThisStock = min($remainingQuantity, $stockUsed->quantity);
                    $profit = $cartItem->discounted_price * $quantityFromThisStock - ($stockUsed->cost * $quantityFromThisStock);
                    $totalProfit += $profit;

                    $transactionItem = DB::table('transaction_items')->insertGetId([
                        'transaction_id' => $transaction->id,
                        'product_id' => $cartItem->product_id,
                        'product_variant_id' => $cartItem->product_variant_id,
                        'quantity' => $quantityFromThisStock,
                        'price' => $cartItem->price,
                        'discount' => $cartItem->discount,
                        'discounted_price' => $cartItem->discounted_price,
                        'total_price' => $cartItem->price * $quantityFromThisStock,
                        'discounted_total_price' => $cartItem->discounted_price * $quantityFromThisStock,
                        'profit' => $profit,
                        'restock_id' => $stockUsed->id,
                        'store_id' => Auth::user()->store_id,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);

                    $stockUsed->quantity -= $quantityFromThisStock;
                    if ($stockUsed->quantity == 0) {
                        $stockUsed->status = 'sold-out';
                    } else if ($stockUsed->quantity < 0) {
                        $stockUsed->status = 'overdrawn';
                    } else {
                        $differenceStock = Restock::where('product_variant_id', $cartItem->product_variant_id)
                            ->where('difference', '>', 0)
                            ->where('status', '!=', 'audit-completed')
                            ->where('store_id', Auth::user()->store_id)
                            ->get();

                        if ($differenceStock->count() > 0) {
                            foreach ($differenceStock as $diffStock) {
                                $diffStock->status = 'audit-needed';
                                $diffStock->save();
                            }
                        }

                        $stockUsed->status = 'in-use';
                    }

                    $stockUsed->save();

                    $remainingQuantity -= $quantityFromThisStock;

                    if ($request->payment_method == 'debt') {
                        $debt = Debt::where('transaction_id', $transaction->id)->where('store_id', Auth::user()->store_id)->first();
                        if (!$debt) {
                            $debt = Debt::create([
                                'customer_id' => $request->customer_id,
                                'transaction_id' => $transaction->id,
                                'total_amount' => $cart->grand_total,
                                'remaining_amount' => $cart->grand_total,
                                'store_id' => Auth::user()->store_id,
                            ]);
                        }

                        DebtItem::create([
                            'debt_id' => $debt->id,
                            'transaction_item_id' => $transactionItem,
                            'total_amount' => $cartItem->discounted_price * $quantityFromThisStock,
                            'remaining_amount' => $cartItem->discounted_price * $quantityFromThisStock,
                            'store_id' => Auth::user()->store_id,
                        ]);
                    }
                }

                $transaction->total_profit += $totalProfit;
            }

            $transaction->save();

            if ($request->payment_method == 'debt') {
                if ($cart->tax > 0) {
                    $debt = Debt::where('transaction_id', $transaction->id)->where('store_id', Auth::user()->store_id)->first();

                    DebtItem::create([
                        'debt_id' => $debt->id,
                        'total_amount' => $cart->tax,
                        'remaining_amount' => $cart->tax,
                        'store_id' => Auth::user()->store_id,
                    ]);
                }
            }

            $cart->delete();

            return response()->json([
                'message' => 'Transaction stored successfully',
                'status' => 'success',
                'data' => $transactionData,
            ]);
        });
        // } catch (\Exception $e) {
        //     // Jika ada error, rollback transaksi dan kirim pesan error
        //     return response()->json([
        //         'status' => 'error',
        //         'message' => $e->getMessage(),
        //     ], 400);
        // }
    }


    public function decreaseStock($cartItems)
    {
        foreach ($cartItems as $cartItem) {
            $productVariant = ProductVariant::where('id', $cartItem->product_variant_id)->where('store_id', Auth::user()->store_id)->first();

            if ($productVariant) {
                if ($productVariant->stock > $cartItem->quantity) {
                    $productVariant->stock -= $cartItem->quantity;

                    if ($productVariant->stock > 5) {
                        $productVariant->stock_status = 'in-stock';
                    } else {
                        $productVariant->stock_status = 'limit-stock';
                    }
                } else {
                    $productVariant->stock = 0;

                    $productVariant->stock_status = 'out-of-stock';
                }
                $productVariant->save();

                $stockMovement = StockMovement::create([
                    'product_variant_id' => $cartItem->product_variant_id,
                    'quantity' => $cartItem->quantity,
                    'type' => 'out',
                    'reference' => 'Transaksi',
                    'created_at' => now(),
                    'updated_at' => now(),
                    'store_id' => Auth::user()->store_id,
                ]);
            }
        }
    }

    public function print($cart, $items, $transactionCode, $payment, $method)
    {
        $ads = Ads::where('type', 'receipt')->first();
        $storeSettings = StoreSetting::where('store_id', Auth::user()->store_id)->get()->keyBy('key');

        // Menghubungkan ke printer dengan nama printer
        $profile = CapabilityProfile::load('simple');
        $connector = new WindowsPrintConnector($storeSettings['printer_name']->value);
        $printer = new Printer($connector, $profile);

        // Nama dan informasi toko
        $tokoName = strtoupper(Auth::user()->store->name) . "\n";
        $tokoAddress = Auth::user()->store->address . "\n";

        // Informasi struk
        $kasir = "Kasir: " . Auth::user()->name;
        $tanggal = "Tanggal: " . date("d-m-Y H:i:s");
        $nomorStruk = "Nomor: " . $transactionCode;

        // Pengaturan format
        $printer->setJustification(Printer::JUSTIFY_CENTER);
        $printer->setTextSize(2, 2);
        $printer->text($tokoName);
        $printer->setTextSize(1, 1);
        $printer->text("\n" . $tokoAddress . "\n\n");

        $printer->setJustification(Printer::JUSTIFY_LEFT);
        $printer->text($kasir . "\n");
        $printer->text($tanggal . "\n");
        $printer->text($nomorStruk . "\n");
        $printer->text(str_repeat("-", 47) . "\n"); // Lebar garis diperkecil untuk memberi margin kanan

        // Header kolom barang
        $printer->setEmphasis(true);
        $printer->text(str_pad("Barang", 30) . str_pad("Subtotal", 16, ' ', STR_PAD_LEFT) . " \n"); // Header dengan margin kanan
        $printer->setEmphasis(false);
        $printer->text(str_repeat("-", 47) . "\n"); // Garis pemisah lebih lebar

        $total = 0;
        foreach ($items as $item) {
            // Nama barang dicetak di baris pertama
            $printer->text($item->product->name . "\n");

            // Detail barang: Qty x Harga, Total dengan indentasi
            $qty = "  " . $item->quantity . " pcs x " . number_format($item->price, 0, ',', '.'); // Tambahan spasi untuk indentasi
            $subtotal = number_format($item->quantity * $item->price, 0, ',', '.'); // Total harga

            // Menampilkan detail barang dengan margin kanan
            $printer->text(
                str_pad($qty, 30) . // Format Qty pcs x Harga dengan indentasi
                    str_pad($subtotal, 16, ' ', STR_PAD_LEFT) . " \n" // Subtotal dengan margin kanan 1 karakter
            );

            $total += $item->quantity * $item->price;
        }

        $printer->text(str_repeat("-", 47) . "\n");

        // Total
        $printer->text(str_pad("Subtotal", 30) . str_pad(number_format($cart->total_price, 0, ',', '.'), 16, ' ', STR_PAD_LEFT) . " \n");
        $discount = $cart->discount > 0 ? '(-' . number_format($cart->discount, 0, ',', '.') . ')' : '0';
        $printer->text(str_pad("Diskon", 30) . str_pad($discount, 16, ' ', STR_PAD_LEFT) . " \n");
        $printer->text(str_pad("PPN (" . $storeSettings['tax_percentage']->value . "%)", 30) . str_pad(number_format($cart->tax, 0, ',', '.'), 16, ' ', STR_PAD_LEFT) . " \n");
        $printer->text(str_repeat("-", 47) . "\n");
        $printer->setEmphasis(true);
        $printer->text(str_pad("Total Akhir", 30) . str_pad(number_format($cart->grand_total, 0, ',', '.'), 16, ' ', STR_PAD_LEFT) . " \n");
        $printer->setEmphasis(false);
        $printer->feed();
        $printer->text(str_pad($method == 'debt' ? 'Catat Hutang' : $method, 30) . str_pad(number_format($payment, 0, ',', '.'), 16, ' ', STR_PAD_LEFT) . " \n");
        if ($method == 'cash') {
            $printer->text(str_pad("Kembali", 30) . str_pad(number_format($payment - $cart->grand_total, 0, ',', '.'), 16, ' ', STR_PAD_LEFT) . " \n");
        }
        $printer->text(str_repeat("-", 47) . "\n\n");

        // Ucapan terima kasih
        $printer->setJustification(Printer::JUSTIFY_CENTER);
        $printer->text("Terima Kasih Atas Kunjungan Anda!\n");
        $printer->feed();

        if ($ads) {
            $printer->text(strtoupper($ads->sponsor_type) . " BY " . strtoupper($ads->sponsor_name) . "\n");
            $printer->text($ads->sponsor_description . "\n");
        }

        $printer->feed(3); // Jarak sebelum potong kertas
        $printer->cut();
        $printer->close();
    }

    public function generateReceipt(Request $request)
    {
        $cart = (object) $request->data['cart'];
        $items = collect($request->data['cartItems'])->map(function ($item) {
            $item = (object) $item;
            $item->product = (object) $item->product;
            return $item;
        });

        Debugbar::info($items);

        $transactionCode = $request->data['transaction_code'];
        $payment = $request->data['total_payment'];
        $method = $request->data['payment_method'];

        $ads = Ads::where('type', 'receipt')->first();
        $storeSettings = StoreSetting::where('store_id', Auth::user()->store_id)
            ->get()
            ->keyBy('key');

        Log::info(Auth::user()->store->name);
        // Generate receipt content
        $content = "";

        $content .= "\x1B\x61\x01";
        $content .= "\x1B\x21\x30";

        $content .=  Auth::user()->store->name . "\n";

        $content .= "\x1B\x21\x00";
        $content .= "\x1B\x61\x00";

        $content .= str_pad(Auth::user()->store->address, 48, " ", STR_PAD_BOTH) . "\n\n";

        // Info transaksi
        $content .= "Kasir: " . Auth::user()->name . "\n";
        $content .= "Tanggal: " . date("d-m-Y H:i:s") . "\n";
        $content .= "Nomor: " . $transactionCode . "\n";
        $content .= str_repeat("-", 47) . "\n";

        // Header items dengan format yang sama seperti referensi
        $content .= str_pad("Barang", 30) . str_pad("Subtotal", 16, ' ', STR_PAD_LEFT) . " \n";
        $content .= str_repeat("-", 47) . "\n";

        // Items dengan format yang sama
        foreach ($items as $item) {
            // Nama barang di baris pertama
            $content .= $item->product->name . "\n";

            // Detail quantity dan harga dengan indentasi
            $qty = "  " . $item->quantity . " pcs x " . number_format($item->price, 0, ',', '.');
            $subtotal = number_format($item->quantity * $item->price, 0, ',', '.');
            $content .= str_pad($qty, 30) . str_pad($subtotal, 16, ' ', STR_PAD_LEFT) . " \n";
        }

        // Totals dengan format yang sama
        $content .= str_repeat("-", 47) . "\n";
        $content .= str_pad("Subtotal", 30) . str_pad(number_format($cart->total_price, 0, ',', '.'), 16, ' ', STR_PAD_LEFT) . " \n";

        // Discount
        $discount = $cart->discount > 0 ? '(-' . number_format($cart->discount, 0, ',', '.') . ')' : '0';
        $content .= str_pad("Diskon", 30) . str_pad($discount, 16, ' ', STR_PAD_LEFT) . " \n";

        // Tax
        $content .= str_pad("PPN (" . $storeSettings['tax_percentage']->value . "%)", 30) . str_pad(number_format($cart->tax, 0, ',', '.'), 16, ' ', STR_PAD_LEFT) . " \n";

        // Grand Total
        $content .= str_repeat("-", 47) . "\n";
        $content .= "\x1B\x45\x01";
        $content .= str_pad("Total Akhir", 30) . str_pad(number_format($cart->grand_total, 0, ',', '.'), 16, ' ', STR_PAD_LEFT) . " \n\n";
        $content .= "\x1B\x45\x00";

        // Payment info
        $payment_method = $method == 'debt' ? 'Catat Hutang' : $method;
        $content .= str_pad($payment_method, 30) . str_pad(number_format($payment, 0, ',', '.'), 16, ' ', STR_PAD_LEFT) . " \n";

        if ($method == 'cash') {
            $content .= str_pad("Kembali", 30) . str_pad(number_format($payment - $cart->grand_total, 0, ',', '.'), 16, ' ', STR_PAD_LEFT) . " \n";
        }

        // Footer
        $content .= str_repeat("-", 47) . "\n\n";
        $content .= str_pad("Terima Kasih Atas Kunjungan Anda!", 47, " ", STR_PAD_BOTH) . "\n\n";

        // Ads jika ada
        if ($ads) {
            $content .= str_pad(strtoupper($ads->sponsor_type) . " BY " . strtoupper($ads->sponsor_name), 47, " ", STR_PAD_BOTH) . "\n";
            $content .= str_pad($ads->sponsor_description, 47, " ", STR_PAD_BOTH);
        }

        return response()->json([
            'status' => 'success',
            'content' => $content
        ]);
    }

    public function printToBrowser(Request $request)
    {
        $cart = (object) $request->data['cart'];
        $items = collect($request->data['cartItems'])->map(function ($item) {
            return (object) $item;
        });
        $transactionCode = $request->data['transaction_code'];
        $payment = $request->data['total_payment'];
        $method = $request->data['payment_method'];

        $ads = Ads::where('type', 'receipt')->first();
        $storeSettings = StoreSetting::where('store_id', Auth::user()->store_id)->get()->keyBy('key');

        $storeName = strtoupper(Auth::user()->store->name);
        $storeAddress = Auth::user()->store->address;

        $html = '<div style="font-family: monospace; width: 100%; max-width: 72mm; margin: 0 auto;">';

        // Header toko
        $html .= '<div style="text-align: center; line-height: 1.2;">';
        $html .= "<h2 style='margin: 0; font-weight: bold;'>$storeName</h2>";
        $html .= "<p style='margin: 0; font-weight: normal;'>$storeAddress</p>";
        $html .= '<hr style="border: none; border-top: 1px solid black; margin: 4px 0;">';
        $html .= '</div>';

        // Informasi transaksi
        $html .= '<div style="line-height: 1.2; font-size: 12px;">';
        $html .= "<p style='margin: 0;'>Kasir: " . Auth::user()->name . "</p>";
        $html .= "<p style='margin: 0;'>Tanggal: " . date("d-m-Y H:i:s") . "</p>";
        $html .= "<p style='margin: 0;'>Nomor: $transactionCode</p>";
        $html .= '<hr style="border: none; border-top: 1px solid black; margin: 4px 0;">';
        $html .= '</div>';

        // Daftar Barang
        $html .= '<table style="width: 100%; font-size: 12px; line-height: 1.2;">';
        $html .= '<tr><th style="text-align: left; font-weight: normal;">Barang</th><th style="text-align: right; font-weight: normal;">Subtotal</th></tr>';
        $total = 0;

        $items = [
            (object) [
                'product' => (object) [
                    'name' => 'Kopi Hitam'
                ],
                'quantity' => 2,
                'price' => 10000
            ],
            (object) [
                'product' => (object) [
                    'name' => 'Kopi Susu'
                ],
                'quantity' => 1,
                'price' => 15000
            ]
        ];

        foreach ($items as $item) {
            $subtotal = $item->quantity * $item->price;
            $total += $subtotal;
            $html .= "<tr>";
            $html .= "<td style='font-weight: normal;'>" . $item->product->name . "</td>";
            $html .= "<td style='text-align: right; font-weight: normal;'>Rp " . number_format($subtotal, 0, ',', '.') . "</td>";
            $html .= "</tr>";
            $html .= "<tr><td colspan='2' style='font-size: 10px; color: #666; font-weight: normal;'>  {$item->quantity} pcs x Rp " . number_format($item->price, 0, ',', '.') . "</td></tr>";
        }
        $html .= '</table>';
        $html .= '<hr style="border: none; border-top: 1px solid black; margin: 4px 0;">';

        // Total dan pembayaran
        $html .= '<table style="width: 100%; font-size: 12px; line-height: 1.2;">';
        $html .= "<tr><td style='font-weight: normal;'>Subtotal</td><td style='text-align: right; font-weight: normal;'>Rp " . number_format($cart->total_price, 0, ',', '.') . "</td></tr>";
        $html .= "<tr><td style='font-weight: normal;'>Diskon</td><td style='text-align: right; font-weight: normal;'>Rp " . number_format($cart->discount, 0, ',', '.') . "</td></tr>";
        $html .= "<tr><td style='font-weight: normal;'>PPN ({$storeSettings['tax_percentage']->value}%)</td><td style='text-align: right; font-weight: normal;'>Rp " . number_format($cart->tax, 0, ',', '.') . "</td></tr>";
        $html .= "<tr><td style='font-weight: bold;'>Total Akhir</td><td style='text-align: right; font-weight: bold;'>Rp " . number_format($cart->grand_total, 0, ',', '.') . "</td></tr>";
        if ($method == 'cash') {
            $html .= "<tr><td style='font-weight: normal;'>Kembali</td><td style='text-align: right; font-weight: normal;'>Rp " . number_format($payment - $cart->grand_total, 0, ',', '.') . "</td></tr>";
        }
        $html .= '</table>';
        $html .= '<hr style="border: none; border-top: 1px solid black; margin: 4px 0;">';

        // Ucapan terima kasih
        $html .= '<div style="text-align: center; line-height: 1.2;">';
        $html .= '<p style="font-weight: normal; margin: 0;">Terima Kasih Atas Kunjungan Anda!</p>';
        if ($ads) {
            $html .= '<p style="font-weight: normal; margin: 0;">' . strtoupper($ads->sponsor_type) . ' BY ' . strtoupper($ads->sponsor_name) . '</p>';
            $html .= '<p style="font-weight: normal; margin: 0;">' . $ads->sponsor_description . '</p>';
        }
        $html .= '</div>';

        $html .= '</div>';


        return response([
            'status' => 'success',
            'content' => $html
        ]);
    }


    /**
     * Mendapatkan diskon per produk berdasarkan ID produk.
     *
     * @param int $productId
     * @return array|null
     */
    protected function getProductDiscount($productId, $quantity)
    {
        return DiscountProduct::where('product_variant_id', $productId)
            ->where('store_id', Auth::user()->store_id)
            ->whereHas('discount', function ($query) use ($quantity) {
                $query->where('threshold', '<=', $quantity);
            })
            ->where('is_active', true)
            ->with(['discount' => function ($query) {
                $query->orderBy('threshold', 'desc');
            }])
            ->first();
    }

    /**
     * Menghitung diskon untuk satu produk.
     *
     * @param float $price
     * @param array $discount
     * @return float
     */
    protected function calculateProductDiscount($price, $discount)
    {
        if ($discount['discount']['amount_type'] == 'percentage') {
            return $price - ($price * ($discount['discount']['amount'] / 100));
        } else {
            return $price - $discount['discount']['amount'];
        }
    }

    /**
     * Mendapatkan diskon total pesanan berdasarkan jumlah belanja.
     *
     * @param float $totalPrice
     * @return array|null
     */
    protected function getOrderDiscount($totalPrice)
    {
        return Discount::where('type', 'order')
            ->where('threshold', '<=', $totalPrice)
            ->where('is_active', true)
            ->where('store_id', Auth::user()->store_id)
            ->first();
    }

    public function calculateTax($totalPrice)
    {
        $is_tax = StoreSetting::where('key', 'is_tax')->where('store_id', Auth::user()->store->id)->first();

        if (!$is_tax) {
            return 0;
        }

        if ($is_tax->value == '1') {
            $tax = StoreSetting::where('key', 'tax_percentage')->where('store_id', Auth::user()->store->id)->first();
            $taxAmount = $totalPrice * ($tax->value / 100);
        } else {
            $taxAmount = 0;
        }

        return $taxAmount;
    }
}
