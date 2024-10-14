<?php

namespace App\Http\Controllers\Admin;

use App\Models\Cart;
use App\Models\Debt;
use Inertia\Inertia;
use App\Models\Stock;
use App\Models\Product;
use App\Models\Setting;
use App\Models\CartItem;
use App\Models\Customer;
use App\Models\DebtItem;
use App\Models\Discount;
use Mike42\Escpos\Printer;
use Illuminate\Http\Request;
use App\Models\StockMovement;
use App\Models\DiscountProduct;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Mike42\Escpos\CapabilityProfile;
use Illuminate\Support\Facades\Cache;
use Barryvdh\Debugbar\Facades\Debugbar;
use Illuminate\Support\Facades\Validator;
use Barryvdh\Debugbar\Twig\Extension\Debug;
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
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        //
    }

    /**
     * Display the specified resource.
     */
    public function show(Cart $cart)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Cart $cart)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Cart $cart)
    {
        //
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
        $query = Cart::query()->with('user');

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

        $cart = Cart::where('user_id', $userId)->first();

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

    /**
     * Add products to cart.
     */
    public function addProduct(Request $request)
    {
        $request->validate([
            'product' => 'required|array',
            'transaction_code' => 'required|string',
        ]);

        return DB::transaction(function () use ($request) {
            $cart = Cart::firstOrCreate(
                ['user_id' => Auth::id(), 'transaction_code' => $request->transaction_code],
                ['total_price' => 0]
            );

            $cartItem = $cart->cartItems()->updateOrCreate(
                [
                    'product_variant_id' => $request->variant_id,
                ],
                [
                    'product_id' => $request->product['id'],
                    'quantity' => DB::raw('quantity + 1'),
                    'price' => $request->product['product_variants'][0]['price'],
                    'total_price' => DB::raw('price * (quantity)'),
                ]
            );

            $cart->update(['total_price' => $cart->cartItems()->sum('total_price')]);

            return response()->json([
                'message' => 'Product added to cart successfully',
                'status' => 'success',
            ]);
        });
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

        $cart = Cart::where('user_id', Auth::id())->where('transaction_code', $request->transaction_code)->first();

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
            ->first();

        if ($existingItem) {
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

        $cacheKey = 'product_' . md5($request->identifier);

        // Try to get product from cache
        $product = Cache::remember($cacheKey, now()->addHours(24), function () use ($request) {
            // Use query builder for faster lookup
            return DB::table('products')
                ->select('products.id', 'products.sku', 'products.name', 'product_variants.id as variant_id', 'product_variants.price', 'product_variants.cost', 'product_variants.status', 'product_variants.quantity', 'units.name as unit_name')
                ->join('product_variants', 'products.id', '=', 'product_variants.product_id')
                ->join('units', 'product_variants.unit_id', '=', 'units.id')
                ->where('products.sku', $request->identifier)->first();
        });

        if (!$product) {
            $products = Cache::remember($cacheKey . '_name', now()->addHours(24), function () use ($request) {
                return DB::table('products')
                    ->select('products.id', 'products.sku', 'products.name', 'product_variants.id as variant_id', 'product_variants.price', 'product_variants.cost', 'product_variants.status', 'product_variants.quantity', 'units.name as unit_name')
                    ->join('product_variants', 'products.id', '=', 'product_variants.product_id')
                    ->join('units', 'product_variants.unit_id', '=', 'units.id')
                    ->where('products.name', 'like', '%' . $request->identifier . '%')
                    ->get();
            });

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
                ->select('products.id', 'products.sku', 'products.name', 'product_variants.id as variant_id', 'product_variants.price', 'product_variants.cost', 'product_variants.status', 'product_variants.quantity', 'units.name as unit_name')
                ->join('product_variants', 'products.id', '=', 'product_variants.product_id')
                ->join('units', 'product_variants.unit_id', '=', 'units.id')
                ->where('products.sku', $request->identifier)
                ->where('product_variants.id', $request->variant_id)
                ->first();
        }

        return DB::transaction(function () use ($request, $product) {
            $cart = DB::table('carts')->where('user_id', Auth::id())
                ->where('transaction_code', $request->transaction_code)
                ->first();

            if (!$cart) {
                $cartId = DB::table('carts')->insertGetId([
                    'user_id' => Auth::id(),
                    'transaction_code' => $request->transaction_code,
                    'total_price' => 0,
                    'discount' => 0,
                    'tax' => 0,
                    'grand_total' => 0,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            } else {
                $cartId = $cart->id;
            }

            $cartItem = DB::table('cart_items')
                ->where('cart_id', $cartId)
                ->where('product_variant_id', $product->variant_id)
                ->first();

            if ($cartItem) {
                DB::table('cart_items')
                    ->where('id', $cartItem->id)
                    ->update([
                        'quantity' => DB::raw('quantity + 1'),
                        'total_price' => DB::raw('price * (quantity)'),
                        'updated_at' => now(),
                    ]);

                $cartItem = CartItem::where('cart_id', $cartId)
                    ->where('product_variant_id', $product->variant_id)
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
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }

            $totalPrice = DB::table('cart_items')
                ->where('cart_id', $cartId)
                ->sum('total_price');

            $totalDiscount = DB::table('cart_items')
                ->where('cart_id', $cartId)
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

        $cart = Cart::where('user_id', Auth::id())
            ->where('transaction_code', $request->transaction_code)
            ->first();

        if (!$cart) {
            return response()->json([
                'message' => 'Cart not found',
                'status' => 'error',
            ], 404);
        }

        $cartItems = CartItem::where('cart_id', $cart->id)->get();

        if ($cartItems->isEmpty()) {
            return response()->json([
                'message' => 'Cart is empty',
                'status' => 'error',
            ], 400);
        }

        $stockMovement = $this->decreaseStock($cartItems);

        // $this->print($cart, $cartItems, $request->transaction_code, $request->total_payment, $request->payment_method);

        return DB::transaction(function () use ($cart, $cartItems, $request) {
            $transaction = DB::table('transactions')->insertGetId([
                'user_id' => Auth::id(),
                'transaction_code' => $cart->transaction_code,
                'total_price' => $cart->total_price,
                'discount' => $cart->discount,
                'tax' => $cart->tax,
                'grand_total' => $cart->grand_total,
                'total_payment' => $request->total_payment,
                'total_change' => $request->total_payment - ($cart->grand_total),
                'payment_method' => $request->payment_method,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            foreach ($cartItems as $cartItem) {
                $transactionItem = DB::table('transaction_items')->insertGetId([
                    'transaction_id' => $transaction,
                    'product_id' => $cartItem->product_id,
                    'product_variant_id' => $cartItem->product_variant_id,
                    'quantity' => $cartItem->quantity,
                    'price' => $cartItem->price,
                    'discount' => $cartItem->discount,
                    'discounted_price' => $cartItem->discounted_price,
                    'total_price' => $cartItem->total_price,
                    'discounted_total_price' => $cartItem->discounted_total_price,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);

                if ($request->payment_method == 'debt') {
                    $debt = Debt::create([
                        'customer_id' => $request->customer_id,
                        'transaction_id' => $transaction,
                        'total_amount' => $cart->grand_total,
                        'remaining_amount' => $cart->grand_total,
                    ]);

                    DebtItem::create([
                        'debt_id' => $debt->id,
                        'transaction_item_id' => $transactionItem,
                        'total_amount' => $cartItem->discounted_total_price,
                        'remaining_amount' => $cartItem->discounted_total_price,
                    ]);
                }
            }

            if ($request->payment_method == 'debt') {
                if ($cart->tax > 0) {
                    $debt = Debt::where('transaction_id', $transaction)->first();

                    DebtItem::create([
                        'debt_id' => $debt->id,
                        'total_amount' => $cart->tax,
                        'remaining_amount' => $cart->tax,
                    ]);
                }
            }

            $cart->delete();

            return response()->json([
                'message' => 'Transaction stored successfully',
                'status' => 'success',
            ]);
        });
    }

    public function decreaseStock($cartItems)
    {
        foreach ($cartItems as $cartItem) {
            $stock = Stock::where('product_variant_id', $cartItem->product_variant_id)->first();

            if ($stock) {
                $stock->quantity -= $cartItem->quantity;
                $stock->save();

                $stockMovement = StockMovement::create([
                    'product_variant_id' => $cartItem->product_variant_id,
                    'quantity' => $cartItem->quantity,
                    'type' => 'out',
                    'reference' => 'transaction',
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }
    }

    public function print($cart, $items, $transactionCode, $payment, $method)
    {
        $globalSettings = Setting::all()->keyBy('key');

        // Menghubungkan ke printer dengan nama printer
        $profile = CapabilityProfile::load('simple');
        $connector = new WindowsPrintConnector($globalSettings['printer_name']->value);
        $printer = new Printer($connector, $profile);

        // Nama dan informasi toko
        $tokoName = $globalSettings['shop_name']->value . "\n";
        $tokoAddress = $globalSettings['shop_address']->value . "\n";

        // Informasi struk
        $kasir = "Kasir: " . Auth::user()->name;
        $tanggal = "Tanggal: " . date("d-m-Y H:i:s");
        $nomorStruk = "Nomor: " . $transactionCode;

        // Pengaturan format
        $printer->setJustification(Printer::JUSTIFY_CENTER);
        $printer->setTextSize(2, 2);
        $printer->text($tokoName . "\n");
        $printer->setTextSize(1, 1);
        $printer->text($tokoAddress . "\n\n");

        $printer->setJustification(Printer::JUSTIFY_LEFT);
        $printer->text($kasir . "\n");
        $printer->text($tanggal . "\n");
        $printer->text($nomorStruk . "\n");
        $printer->text(str_repeat("-", 47) . "\n"); // Lebar garis diperkecil untuk memberi margin kanan

        // Header kolom barang
        $printer->setEmphasis(true);
        $printer->text(str_pad("Barang", 30) . str_pad("Total", 16, ' ', STR_PAD_LEFT) . " \n"); // Header dengan margin kanan
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
        $printer->text(str_pad("Total", 30) . str_pad(number_format($cart->total_price, 0, ',', '.'), 16, ' ', STR_PAD_LEFT) . " \n");
        $printer->text(str_pad("Diskon", 30) . str_pad($cart->discount > 0 ? '- ' : '' . number_format($cart->discount, 0, ',', '.'), 16, ' ', STR_PAD_LEFT) . " \n");
        $printer->text(str_pad("PPN", 30) . str_pad(number_format($cart->tax, 0, ',', '.'), 16, ' ', STR_PAD_LEFT) . " \n");
        $printer->text(str_repeat("-", 47) . "\n");
        $printer->setEmphasis(true);
        $printer->text(str_pad("Total Belanja", 30) . str_pad(number_format($cart->grand_total, 0, ',', '.'), 16, ' ', STR_PAD_LEFT) . " \n");
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
        // $printer->text("Barang yang sudah dibeli tidak dapat\n");
        // $printer->text("dikembalikan.\n");
        $printer->feed();
        $printer->text("CSR By PT. SUCOFINDO CILACAP\n");
        $printer->text("--- SUCOFINDO untuk UMKM ---\n");
        $printer->feed(3); // Jarak sebelum potong kertas
        $printer->cut();
        $printer->close();
    }



    public function applyDiscounts($cartItems, $totalPrice)
    {
        // 1. Cek diskon untuk setiap produk (jika ada)
        foreach ($cartItems as $item) {
            $productDiscount = $this->getProductDiscount($item->product_id, $item->quantity);

            if ($productDiscount) {
                $item->discounted_price = $this->calculateProductDiscount($item->price, $productDiscount);
            } else {
                $item->discounted_price = $item->price;
            }
        }

        // 2. Cek diskon total pesanan (general order discount)
        $orderDiscount = $this->getOrderDiscount($totalPrice);
        $totalDiscountAmount = 0; // Total diskon untuk pesanan
        if ($orderDiscount) {
            $totalDiscountAmount = $orderDiscount['amount_type'] == 'percentage'
                ? ($totalPrice * ($orderDiscount['amount'] / 100))
                : $orderDiscount['amount'];

            $totalPrice -= $totalDiscountAmount;
        }

        return [
            'cartItems' => $cartItems,
            'totalPrice' => $totalPrice,
            'totalDiscount' => $totalDiscountAmount,
        ];
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
            ->whereHas('discount', function ($query) use ($quantity) {
                $query->where('threshold', '<=', $quantity);
            })
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

    // protected function calculateProductDiscountTotal($total_price, $discount, $quantity)
    // {
    //     if ($discount['discount']['amount_type'] == 'percentage') {
    //         return ($total_price - ($total_price * ($discount['discount']['amount'] / 100)));
    //     } else {
    //         return ($total_price - $discount['amount']) * $quantity;
    //     }
    // }

    /**
     * Mendapatkan diskon total pesanan berdasarkan jumlah belanja.
     *
     * @param float $totalPrice
     * @return array|null
     */
    protected function getOrderDiscount($totalPrice)
    {
        return Discount::where('type', 'order')
            ->where('threshold', '<=', $totalPrice)->first();
    }

    public function calculateTax($totalPrice)
    {
        $is_tax = Setting::where('key', 'is_tax')->first();

        if ($is_tax->value == '1') {
            $tax = Setting::where('key', 'tax_percentage')->first();
            $taxAmount = $totalPrice * ($tax->value / 100);
        } else {
            $taxAmount = 0;
        }

        return $taxAmount;
    }
}
