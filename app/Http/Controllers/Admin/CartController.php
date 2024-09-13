<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Cart;
use Illuminate\Http\Request;
use Inertia\Inertia;

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
}
