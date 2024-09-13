<?php

namespace App\Http\Controllers\Admin;

use Inertia\Inertia;
use App\Models\CartItem;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;

class CartItemController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        return Inertia::render('Admin/CartItems');
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
    public function show(CartItem $cartItem)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(CartItem $cartItem)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, CartItem $cartItem)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(CartItem $cartItem)
    {
        $cartItem->delete();

        return response()->json([
            'message' => 'Item Transaksi Sementara Berhasil Dihapus',
            'status' => 'success',
        ]);
    }

    /**
     * Get all cartItems data.
     */
    public function cartItemData(Request $request)
    {
        $query = CartItem::query()->with('cart', 'product');

        // Handle global search
        if ($request->has('search')) {
            $searchTerm = $request->search;
            $query->where(function ($q) use ($searchTerm) {
                $q->Where('total_price', 'like', "%{$searchTerm}%")
                    ->orWhereHas('product', function ($q) use ($searchTerm) {
                        $q->where('name', 'like', "%{$searchTerm}%");
                    })
                    ->orWhereHas('cart', function ($q) use ($searchTerm) {
                        $q->where('transaction_code', 'like', "%{$searchTerm}%");
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
        $cartItems = $query->paginate($perPage);


        // Calculate 'from' and 'to'
        $from = ($cartItems->currentPage() - 1) * $cartItems->perPage() + 1;
        $to = min($from + $cartItems->count() - 1, $cartItems->total());

        $transformedCartItems = $cartItems->map(function ($cartItem) {
            return [
                'id' => $cartItem->id,
                'cart' => $cartItem->cart->transaction_code,
                'product' => $cartItem->product->name,
                'quantity' => $cartItem->quantity,
                'price' => $cartItem->price,
                'total_price' => $cartItem->total_price,
            ];
        });

        return response()->json([
            'data' => $transformedCartItems,
            'meta' => [
                'current_page' => $cartItems->currentPage(),
                'last_page' => $cartItems->lastPage(),
                'per_page' => $cartItems->perPage(),
                'total' => $cartItems->total(),
                'from' => $from,
                'to' => $to,
            ],
        ]);
    }
}
