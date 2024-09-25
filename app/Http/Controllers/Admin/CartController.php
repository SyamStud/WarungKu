<?php

namespace App\Http\Controllers\Admin;

use App\Models\Cart;
use Inertia\Inertia;
use App\Models\CartItem;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Barryvdh\Debugbar\Facades\Debugbar;

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
        $cart = Cart::where('user_id', Auth::id())->first();

        if (!$cart) {
            return response()->json([
                'message' => 'Cart is empty',
                'status' => 'success',
                'data' => [],
            ]);
        }

        $cartItems = $cart->cartItems()->with('productVariant')->get();

        $transformedCartItems = $cartItems->map(function ($cartItem) {
            return [
                'sku' => $cartItem->product->sku,
                'id' => $cartItem->id,
                'product_id' => $cartItem->product_id,
                'name' => $cartItem->product->name,
                'quantity' => $cartItem->quantity,
                'price' => $cartItem->price,
                'total_price' => $cartItem->total_price,
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
            'message' => 'Cart items retrieved successfully',
            'status' => 'success',
            'data' => $transformedCartItems,
            'total_price' => $cart->total_price,
            'transaction_code' => $cart->transaction_code,
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

        $cartItem->update([
            'quantity' => $request->quantity,
            'total_price' => $cartItem->price * $request->quantity,
        ]);

        $cart = Cart::find($cartItem->cart_id);
        $totalPrice = $cart->cartItems()->sum('total_price');
        $cart->update(['total_price' => $totalPrice]);

        return response()->json([
            'message' => 'Product quantity updated successfully',
            'status' => 'success',
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
            // If exists, update the quantity and delete the original item
            $existingItem->update([
                'quantity' => $existingItem->quantity + $cartItem->quantity,
                'total_price' => ($existingItem->quantity + $cartItem->quantity) * $newVariant->price,
            ]);
            $cartItem->delete();
        } else {
            // If not exists, update the current item
            $cartItem->update([
                'product_variant_id' => $request->variant_id,
                'price' => $newVariant->price,
                'total_price' => $newVariant->price * $cartItem->quantity,
            ]);
        }

        $cart = Cart::find($cartItem->cart_id);
        $totalPrice = $cart->cartItems()->sum('total_price');
        $cart->update(['total_price' => $totalPrice]);

        $transformedCartItems = $cart->cartItems->map(function ($cartItem) {
            return [
                'sku' => $cartItem->product->sku,
                'id' => $cartItem->id,
                'product_id' => $cartItem->product_id,
                'name' => $cartItem->product->name,
                'quantity' => $cartItem->quantity,
                'price' => $cartItem->price,
                'total_price' => $cartItem->total_price,
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
        ]);
    }
}
