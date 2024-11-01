<?php

namespace App\Http\Controllers\Admin;

use Inertia\Inertia;
use App\Models\Product;
use Illuminate\Http\Request;
use App\Models\ProductVariant;
use App\Exports\ProductsExport;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Maatwebsite\Excel\Facades\Excel;
use App\Exports\ProductVariantsExport;
use Barryvdh\Debugbar\Facades\Debugbar;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Validator;

class ProductVariantController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        return Inertia::render('Admin/ProductVariants');
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
        $validation = Validator::make($request->all(), [
            'product_id' => 'required|exists:products,id',
            'status' => 'required',
            'variantInputs' => 'required|array',
        ]);

        if ($validation->fails()) {
            return response()->json([
                'message' => $validation->errors(),
                'errors' => $validation->errors(),
                'status' => 'error',
            ]);
        }

        foreach ($request->variantInputs as $inputs) {
            Debugbar::info($inputs);

            $isExist = ProductVariant::where('product_id', $request->product_id)->where('unit_id', $inputs['unit_id'])->exists();

            if ($isExist) {
                return response()->json([
                    'message' => 'Variasi Produk sudah ada',
                    'status' => 'error',
                ]);
            }
        }

        $product = Product::find($request->product_id);

        $productVariants = collect($request->variantInputs)->map(function ($variant) use ($request) {
            return array_merge($variant, ['status' => $request->status]);
        });

        $createdVariants = $product->productVariants()->createMany(
            $productVariants->map(function ($variant) use ($request) {
                $variant['store_id'] = Auth::user()->store->id;
                return $variant;
            })->toArray()
        );

        foreach ($createdVariants as $index => $variant) {
            $variant->stock = isset($request->variantInputs[$index]['stock']) ? $request->variantInputs[$index]['stock'] : 0;

            if ($variant->stock == 0) {
                $variant->stock_status = 'not-set';
            } else if ($variant->stock < 10) {
                $variant->stock_status = 'limit-stock';
            } else if ($variant->stock >= 10) {
                $variant->stock_status = 'in-stock';
            } else {
                $variant->stock_status = 'out-of-stock';
            }

            $variant->save();

            $status = null;

            if ($variant->stock == 0) {
                $status = 'sold-out';
            } else if ($variant->stock > 0) {
                $status = 'available';
            }

            $variant->restocks()->create([
                'quantity' => $variant->stock,
                'cost' => $request->variantInputs[$index]['cost'],
                'stock_status' => $status,
                'store_id' => Auth::user()->store->id,
            ]);
        }

        return response()->json([
            'message' => 'Produk berhasil ditambahkan',
            'product' => $product,
            'status' => 'success',
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, ProductVariant $productVariant)
    {
        $validation = Validator::make($request->all(), [
            'price' => 'required|numeric',
            'status' => 'required',
        ], [
            'sku.unique' => 'SKU sudah terdaftar.',
        ]);

        if ($validation->fails()) {
            return response()->json([
                'message' => $validation->errors()->first(),
                'errors' => $validation->errors(),
                'status' => 'error',
            ]);
        }

        $productVariant->update([
            'price' => $request->price,
            'status' => $request->status,
        ]);

        return response()->json([
            'message' => 'Produk berhasil diubah',
            'product' => $productVariant,
            'status' => 'success',
        ]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(ProductVariant $productVariant)
    {
        Gate::authorize('delete', $productVariant);

        $productVariant->delete();

        return response()->json([
            'status' => 'success',
            'message' => 'Produk berhasil dihapus',
        ]);
    }

    public function exportExcel()
    {
        return Excel::download(new ProductVariantsExport, 'variasi-produk-' . now() . '.xlsx');
    }
}
