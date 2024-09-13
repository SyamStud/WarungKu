<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\Stock;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Inertia\Inertia;

class ProductController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        return Inertia::render('Admin/Products');
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
            'sku' => 'required|string|max:255|unique:products,sku',
            'name' => 'required|string|max:255',
            'price' => 'required|numeric',
            'cost' => 'numeric',
            'description' => 'nullable|string',
        ], [
            'sku.unique' => 'SKU sudah terdaftar.',
        ]);

        if ($validation->fails()) {
            return response()->json([
                'message' => $validation->errors(),
                'errors' => $validation->errors(),
                'status' => 'error',
            ]);
        }

        $product = Product::create($request->except('stock'));

        $stock = new Stock();
        $stock->product_id = $product->id;
        $stock->quantity = $request->stock ? $request->stock : 0;

        if ($request->stock == 0) {
            $stock->status = 'not-set';
        } else if ($request->stock < 10) {
            $stock->status = 'limit-stock';
        } else if ($request->stock >= 10) {
            $stock->status = 'in-stock';
        } else {
            $stock->status = 'out-of-stock';
        }

        $stock->save();

        return response()->json([
            'message' => 'Produk berhasil ditambahkan',
            'product' => $product,
            'status' => 'success',
        ]);
    }

    /**
     * Display the specified resource.
     */
    public function show(Product $product)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Product $product)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Product $product)
    {
        $validation = Validator::make($request->all(), [
            'sku' => 'required|string|max:255|unique:products,sku,' . $product->id,
            'name' => 'required|string|max:255',
            'category_id' => 'exists:categories,id',
            'price' => 'required|numeric',
            'cost' => 'numeric',
            'description' => 'nullable|string',
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

        $product->update($request->all());

        return response()->json([
            'message' => 'Produk berhasil diubah',
            'product' => $product,
            'status' => 'success',
        ]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Product $product)
    {
        $product->delete();

        return response()->json([
            'message' => 'Produk berhasil dihapus',
            'status' => 'success',
        ]);
    }

    /**
     * Get all products data.
     */
    public function productData(Request $request)
    {
        $query = Product::query()->with('category')->with('stock');

        // Handle global search
        if ($request->has('search')) {
            $searchTerm = $request->search;
            $query->where(function ($q) use ($searchTerm) {
                $q->where('name', 'like', "%{$searchTerm}%");
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
        $products = $query->paginate($perPage);


        // Calculate 'from' and 'to'
        $from = ($products->currentPage() - 1) * $products->perPage() + 1;
        $to = min($from + $products->count() - 1, $products->total());

        $transformedProducts = $products->map(function ($product) {
            return [
                'id' => $product->id,
                'sku' => $product->sku,
                'name' => $product->name,
                'category' => $product->category_id ? $product->category->name : '-',
                'price' => $product->price,
                'cost' => $product->cost ? $product->cost : '-',
                'description' => $product->description ? $product->description : '-',
                'status' => $product->status,
                'stock' => $product->stock ? $product->stock->quantity : 0,
            ];
        });

        return response()->json([
            'data' => $transformedProducts,
            'meta' => [
                'current_page' => $products->currentPage(),
                'last_page' => $products->lastPage(),
                'per_page' => $products->perPage(),
                'total' => $products->total(),
                'from' => $from,
                'to' => $to,
            ],
        ]);
    }

    public function checkSKU(Request $request)
    {
        $validation = Validator::make($request->all(), [
            'sku' => 'required|string|max:255',
        ]);

        if ($validation->fails()) {
            return response()->json([
                'message' => 'The given data was invalid.',
                'errors' => $validation->errors(),
            ], 422);
        }

        $product = Product::where('sku', $request->sku)->first();

        return response()->json([
            'unique' => $product ? false : true,
            'sku' => $request->sku,
        ]);
    }

    public function bulk()
    {
        return Inertia::render('Admin/ProductsBulk');
    }
}
