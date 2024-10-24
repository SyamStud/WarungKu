<?php

namespace App\Http\Controllers\Admin;

use Inertia\Inertia;
use App\Models\Discount;
use Illuminate\Http\Request;
use App\Models\DiscountProduct;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class DiscountProductController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        return Inertia::render('Admin/DiscountProducts');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validation = Validator::make($request->all(), [
            'discount_id' => 'required',
            'product_variant_id' => 'required',
            'is_active' => 'required',
        ]);

        if ($validation->fails()) {
            return response()->json([
                'errors' => $validation->errors(),
            ], 422);
        }

        DiscountProduct::create([
            'discount_id' => $request->discount_id,
            'product_variant_id' => $request->product_variant_id,
            'is_active' => $request->is_active,
            'store_id' => Auth::user()->store->id,
        ]);

        return response()->json([
            'status' => 'success',
            'message' => 'Discount product created successfully',
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, DiscountProduct $discountProduct)
    {
        $validation = Validator::make($request->all(), [
            'is_active' => 'required',
        ]);

        if ($validation->fails()) {
            return response()->json([
                'errors' => $validation->errors(),
            ], 422);
        }

        $discountProduct->is_active = $request->is_active;
        $discountProduct->save();

        return response()->json([
            'status' => 'success',
            'message' => 'Produk Diskon Berhasil Diupdate',
        ]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(DiscountProduct $discountProduct)
    {
        $discountProduct->delete();

        return response()->json([
            'status' => 'success',
            'message' => 'Discount product deleted successfully',
        ]);
    }

    public function discountProductData(Request $request)
    {
        $query = DiscountProduct::query()->where('store_id', Auth::user()->store->id)->with(['discount', 'productVariant.product', 'productVariant.unit']);

        // Handle global search
        if ($request->has('search')) {
            $searchTerm = $request->search;
            $query->where(function ($q) use ($searchTerm) {
                $q->whereHas('discount', function ($q) use ($searchTerm) {
                    $q->where('name', 'like', '%' . $searchTerm . '%');
                })
                    ->orWhereHas('productVariant.product', function ($q) use ($searchTerm) {
                        $q->where('name', 'like', '%' . $searchTerm . '%');
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
        $discounts = $query->paginate($perPage);


        // Calculate 'from' and 'to'
        $from = ($discounts->currentPage() - 1) * $discounts->perPage() + 1;
        $to = min($from + $discounts->count() - 1, $discounts->total());

        $transformedDiscounts = $discounts->map(function ($discount) {

            return [
                'id' => $discount->id,
                'name' => $discount->name,
                'product' => $discount->productVariant->product->name,
                'product_variant' => $discount->productVariant->quantity . ' ' . $discount->productVariant->unit->name,
                'discount' => $discount->discount->name,
                'discount_type' => $discount->discount->type,
                'discount_amount' => $discount->discount->amount,
                'discount_amount_type' => $discount->discount->amount_type,
                'discount_threshold' => $discount->discount->threshold,
                'discount_start_date' => $discount->discount->start_date,
                'discount_end_date' => $discount->discount->end_date,
                'is_active' => $discount->is_active,
            ];
        });

        return response()->json([
            'data' => $transformedDiscounts,
            'meta' => [
                'current_page' => $discounts->currentPage(),
                'last_page' => $discounts->lastPage(),
                'per_page' => $discounts->perPage(),
                'total' => $discounts->total(),
                'from' => $from,
                'to' => $to,
            ],
        ]);
    }
}
