<?php

namespace App\Http\Controllers\Admin;

use Inertia\Inertia;
use App\Models\Discount;
use Illuminate\Http\Request;
use App\Models\DiscountProduct;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Validator;

class DiscountController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        return Inertia::render('Admin/Discounts');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validation = Validator::make($request->all(), [
            'name' => 'required',
            'type' => 'required',
            'start_date' => 'required',
            'end_date' => 'required',
            'is_active' => 'required',
        ]);

        if ($validation->fails()) {
            return response()->json([
                'errors' => $validation->errors(),
            ], 422);
        }

        $isOrderDiscount = Discount::where('type', 'order')->where('is_active', 1)->count();

        if ($request->type == 'order' && $isOrderDiscount > 0) {
            return response()->json([
                'status' => 'error',
                'message' => 'Sudah ada potongan harga belanja yang aktif',
            ]);
        }

        if ($request->amount > 100 && $request->amount_type == 'percentage') {
            return response()->json([
                'status' => 'error',
                'message' => 'Diskon tidak boleh lebih dari 100%',
            ]);
        }

        if ($request->start_date > $request->end_date) {
            return response()->json([
                'status' => 'error',
                'message' => 'Tanggal mulai tidak valid',
            ]);
        }

        $discount = new Discount();
        $discount->name = $request->name;
        $discount->description = $request->description;
        $discount->type = $request->type;
        $discount->amount = $request->amount;
        $discount->amount_type = $request->amount_type;
        $discount->threshold = $request->threshold;
        $discount->start_date = date('Y-m-d', strtotime($request->start_date));
        $discount->end_date = date('Y-m-d', strtotime($request->end_date));
        $discount->is_active = $request->is_active;
        $discount->save();

        if ($request->type == 'product') {
            $discountProduct = new DiscountProduct();
            $discountProduct->discount_id = $discount->id;
            $discountProduct->product_variant_id = $request->product_id;
            $discountProduct->is_active = $request->is_active;
            $discountProduct->save();
        }

        return response()->json([
            'status' => 'success',
            'message' => 'Diskon berhasil ditambahkan',
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Discount $discount)
    {
        $validation = Validator::make($request->all(), [
            'name' => 'required',
            'type' => 'required',
            'start_date' => 'required',
            'end_date' => 'required',
            'is_active' => 'required',
        ]);

        if ($validation->fails()) {
            return response()->json([
                'errors' => $validation->errors(),
            ], 422);
        }

        $discount->update($request->all());

        return response()->json([
            'status' => 'success',
            'message' => 'Diskon berhasil diubah',
        ]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Discount $discount)
    {
        $discount->delete();

        return response()->json([
            'status' => 'success',
            'message' => 'Diskon berhasil dihapus',
        ]);
    }

    public function discountData(Request $request)
    {
        $query = Discount::query();

        // Handle global search
        if ($request->has('search')) {
            $searchTerm = $request->search;
            $query->where(function ($q) use ($searchTerm) {
                $q->where('name', 'like', '%' . $searchTerm . '%')
                    ->orWhere('type', 'like', '%' . $searchTerm . '%')
                    ->orWhere('amount', 'like', '%' . $searchTerm . '%')
                    ->orWhere('amount_type', 'like', '%' . $searchTerm . '%')
                    ->orWhere('start_date', 'like', '%' . $searchTerm . '%')
                    ->orWhere('end_date', 'like', '%' . $searchTerm . '%');
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
                'description' => $discount->description,
                'type' => $discount->type,
                'amount' => $discount->amount,
                'amount_type' => $discount->amount_type,
                'threshold' => $discount->threshold,
                'start_date' => date('Y-m-d', strtotime($discount->start_date)),
                'end_date' => date('Y-m-d', strtotime($discount->end_date)),
                'is_active' => (string) $discount->is_active,
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
