<?php

namespace App\Http\Controllers\Admin;

use Inertia\Inertia;
use App\Models\Stock;
use App\Models\Product;
use Illuminate\Http\Request;
use App\Models\ProductVariant;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Cache;
use Barryvdh\Debugbar\Facades\Debugbar;
use Illuminate\Support\Facades\Validator;
use Barryvdh\Debugbar\Twig\Extension\Debug;

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
            'category_id' => 'exists:categories,id',
            'sku' => 'required|string|max:255|unique:products,sku',
            'name' => 'required|string|max:255',
            'status' => 'required',
            'variantInputs' => 'required|array',
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

        $product = Product::create($request->except('status', 'varianInputs'));

        $productVariants = collect($request->variantInputs)->map(function ($variant) use ($request) {
            return array_merge($variant, ['status' => $request->status]);
        });

        $createdVariants = $product->productVariants()->createMany($productVariants->toArray());

        foreach ($createdVariants as $index => $variant) {
            Debugbar::info($variant);

            $stock = new Stock();
            $stock->product_variant_id = $variant->id;
            $stock->quantity = isset($request->variantInputs[$index]['stock']) ? $request->variantInputs[$index]['stock'] : 0;

            if ($stock->quantity == 0) {
                $stock->status = 'not-set';
            } else if ($stock->quantity < 10) {
                $stock->status = 'limit-stock';
            } else if ($stock->quantity >= 10) {
                $stock->status = 'in-stock';
            } else {
                $stock->status = 'out-of-stock';
            }

            $stock->save();
        }

        return response()->json([
            'message' => 'Produk berhasil ditambahkan',
            'product' => $product,
            'status' => 'success',
        ]);
    }

    public function storeVariant(Request $request)
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

        $product = Product::find($request->product_id);

        $productVariants = collect($request->variantInputs)->map(function ($variant) use ($request) {
            return array_merge($variant, ['status' => $request->status]);
        });

        $createdVariants = $product->productVariants()->createMany($productVariants->toArray());

        foreach ($createdVariants as $index => $variant) {
            Debugbar::info($variant);

            $stock = new Stock();
            $stock->product_variant_id = $variant->id;
            $stock->quantity = isset($request->variantInputs[$index]['stock']) ? $request->variantInputs[$index]['stock'] : 0;

            if ($stock->quantity == 0) {
                $stock->status = 'not-set';
            } else if ($stock->quantity < 10) {
                $stock->status = 'limit-stock';
            } else if ($stock->quantity >= 10) {
                $stock->status = 'in-stock';
            } else {
                $stock->status = 'out-of-stock';
            }

            $stock->save();
        }

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
            'status' => 'required',
            'quantity' => 'required',
            'unit_id' => 'required|exists:units,id',
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

        $product->update($request->only('sku', 'name', 'category_id'));

        $product->productVariants->each(function ($variant) use ($request) {
            $variant->update([
                'price' => $request->price,
                'cost' => $request->cost,
                'status' => $request->status,
                'quantity' => $request->quantity,
                'unit_id' => $request->unit_id,
            ]);
        });

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

    public function productData(Request $request)
    {
        $query = Product::query()->with('category', 'productVariants');

        // Handle global search
        if ($request->has('search')) {
            $searchTerm = $request->search;
            $query->where(function ($q) use ($searchTerm) {
                $q->where('sku', 'like', '%' . $searchTerm . '%')
                    ->orWhere('name', 'like', '%' . $searchTerm . '%')
                    ->orWhereHas('category', function ($q) use ($searchTerm) {
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
        $products = $query->paginate($perPage);


        // Calculate 'from' and 'to'
        $from = ($products->currentPage() - 1) * $products->perPage() + 1;
        $to = min($from + $products->count() - 1, $products->total());

        $transformedProducts = $products->map(function ($product) {
            return [
                'id' => $product->id,
                'sku' => $product->sku,
                'name' => $product->name,
                'category' => $product->category->name,
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

    /**
     * Get all products data.
     */
    public function productVariantData(Request $request)
    {
        $query = ProductVariant::query()->with('product', 'unit', 'stocks');

        // Handle global search
        if ($request->has('search')) {
            $searchTerm = $request->search;
            $query->where(function ($q) use ($searchTerm) {
                $q->whereHas('product', function ($q) use ($searchTerm) {
                    $q->where('sku', 'like', '%' . $searchTerm . '%')
                        ->orWhere('name', 'like', '%' . $searchTerm . '%')
                        ->orWhereHas('category', function ($q) use ($searchTerm) {
                            $q->where('name', 'like', '%' . $searchTerm . '%');
                        });
                })
                    ->orWhereHas('unit', function ($q) use ($searchTerm) {
                        $q->where('name', 'like', '%' . $searchTerm . '%');
                    })
                    ->orWhere('quantity', 'like', '%' . $searchTerm . '%')
                    ->orWhere('price', 'like', '%' . $searchTerm . '%')
                    ->orWhere('cost', 'like', '%' . $searchTerm . '%')
                    ->orWhere('status', 'like', '%' . $searchTerm . '%');
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
                'sku' => $product->product->sku,
                'name' => $product->product->name,
                'category' => $product->product->category->name,
                'price' => $product->price,
                'cost' => $product->cost,
                'status' => $product->status,
                'quantity' => $product->quantity,
                'unit' => $product->unit->name,
                'variant' => $product->quantity . ' ' . $product->unit->name,
                'stock' => $product->stocks->sum('quantity'),
                'unit_id' => $product->unit->id
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

    public function add()
    {
        return Inertia::render('Admin/ProductsAdd');
    }

    public function addVariant()
    {
        return Inertia::render('Admin/ProductsAddVariant');
    }



    public function getProduct(Request $request)
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

        // Use cache to store and retrieve transformed product data
        $cacheKey = 'product_' . $request->sku;
        $transformedProduct = Cache::remember($cacheKey, now()->addHours(24), function () use ($request) {
            $product = Product::where('sku', $request->sku)->with('productVariants')->first();

            if (!$product) {
                return null; // Return null if product not found
            }

            // Transform the product data
            return array_merge(
                $product->toArray(),
                [
                    'variant_id' => (string) $product->productVariants->first()->id,
                    'product_variants' => $product->productVariants->map(function ($variant) {
                        return [
                            'id' => $variant->id,
                            'variant' => $variant->quantity == '1' ? $variant->unit->name : $variant->quantity . ' ' . $variant->unit->name,
                            'price' => $variant->price,
                        ];
                    })->toArray(),
                ]
            );
        });

        if (!$transformedProduct) {
            return response()->json([
                'message' => 'Produk tidak ditemukan',
            ]);
        }

        return response()->json([
            'product' => $transformedProduct,
            'source' => 'sku',
            'cache_info' => [
                'source' => 'sku',
                'key' => $cacheKey,
                'expires_at' => now()->addHours(24)->toIso8601String(),
            ],
        ]);
    }


    public function getProductVariantByName(Request $request)
    {
        $validation = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
        ]);

        if ($validation->fails()) {
            return response()->json([
                'message' => 'The given data was invalid.',
                'errors' => $validation->errors(),
            ], 422);
        }

        $cacheKey = 'product_name_' . $request->name;

        // Cek apakah ada cache yang sudah ada
        $cachedProducts = Cache::get($cacheKey);
        if ($cachedProducts) {
            // Jika ada, langsung kembalikan cache yang sudah di-transformasi
            return response()->json([
                'product' => $cachedProducts,
                'source' => 'name',
                'cache_info' => [
                    'source' => 'cache',
                    'key' => $cacheKey,
                    'expires_at' => now()->addHours(24)->toIso8601String(),
                ],
            ]);
        }

        // Jika tidak ada cache, lakukan query dan mapping
        $products = ProductVariant::whereHas('product', function ($query) use ($request) {
            $query->where('name', 'like', '%' . $request->name . '%');
        })->with('product:id,sku,name', 'unit:id,name', 'stocks:id,product_variant_id,quantity')->get();

        if ($products->isEmpty()) {
            return response()->json([
                'message' => 'Produk tidak ditemukan',
            ]);
        }

        // Mapping produk
        $transformedProducts = $products->map(function ($productVariant) {
            return [
                'id' => $productVariant->id,
                'sku' => $productVariant->product->sku,
                'name' => $productVariant->product->name,
                'price' => $productVariant->price,
                'cost' => $productVariant->cost,
                'status' => $productVariant->status,
                'quantity' => $productVariant->quantity,
                'unit' => $productVariant->unit->name,
                'variant' => $productVariant->quantity == '1' ? $productVariant->unit->name : $productVariant->quantity . ' ' . $productVariant->unit->name,
                'stock' => $productVariant->stocks->sum('quantity'),
                'unit_id' => $productVariant->unit->id
            ];
        });

        // Simpan hasil mapping ke cache
        Cache::put($cacheKey, $transformedProducts, now()->addHours(24));

        return response()->json([
            'product' => $transformedProducts,
            'cache_info' => [
                'source' => 'name',
                'key' => $cacheKey,
                'expires_at' => now()->addHours(24)->toIso8601String(),
            ],
        ]);
    }

    public function getProductByName(Request $request)
    {
        $validation = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
        ]);

        if ($validation->fails()) {
            return response()->json([
                'message' => 'The given data was invalid.',
                'errors' => $validation->errors(),
            ], 422);
        }

        $products = Product::where('name', 'like', '%' . $request->name . '%')->with('productVariants')->get();

        if ($products->isEmpty()) {
            return response()->json([
                'message' => 'Produk tidak ditemukan',
            ]);
        }

        return response()->json([
            'product' => $products,
        ]);
    }
}
