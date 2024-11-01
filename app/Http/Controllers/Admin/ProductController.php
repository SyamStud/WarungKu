<?php

namespace App\Http\Controllers\Admin;

use Inertia\Inertia;
use App\Models\Product;
use Illuminate\Http\Request;
use App\Models\ProductVariant;
use App\Exports\ProductsExport;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Maatwebsite\Excel\Facades\Excel;
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

    public function create()
    {
        return Inertia::render('Admin/ProductsAdd');
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

        $isProductExist = Product::where('sku', $request->sku)
            ->where('category_id', $request->category_id)
            ->where('name', $request->name)
            ->exists();

        if ($isProductExist) {
            return response()->json([
                'message' => 'Produk sudah terdaftar',
                'status' => 'error',
            ]);
        }

        $product = Product::create(array_merge(
            $request->except('status', 'variantInputs'),
            ['store_id' => Auth::user()->store->id]
        ));

        $productVariants = collect($request->variantInputs)->map(function ($variant) use ($request) {
            return array_merge($variant, ['status' => $request->status]);
        });

        $createdVariants = $product->productVariants()->createMany(
            $productVariants->map(function ($variant) use ($product) {
                return array_merge($variant, ['store_id' => $product->store_id]);
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
                'store_id' => $product->store_id,
            ]);
        }

        return response()->json([
            'message' => 'Produk berhasil ditambahkan',
            'product' => $product,
            'status' => 'success',
        ]);
    }

    // public function storeVariant(Request $request)
    // {
    //     $validation = Validator::make($request->all(), [
    //         'product_id' => 'required|exists:products,id',
    //         'status' => 'required',
    //         'variantInputs' => 'required|array',
    //     ]);

    //     if ($validation->fails()) {
    //         return response()->json([
    //             'message' => $validation->errors(),
    //             'errors' => $validation->errors(),
    //             'status' => 'error',
    //         ]);
    //     }

    //     $product = Product::find($request->product_id);

    //     $productVariants = collect($request->variantInputs)->map(function ($variant) use ($request) {
    //         return array_merge($variant, ['status' => $request->status]);
    //     });

    //     $createdVariants = $product->productVariants()->createMany($productVariants->toArray());

    //     foreach ($createdVariants as $index => $variant) {
    //         $variant->stock = isset($request->variantInputs[$index]['stock']) ? $request->variantInputs[$index]['stock'] : 0;

    //         if ($variant->stock == 0) {
    //             $variant->stock_status = 'not-set';
    //         } else if ($variant->stock < 10) {
    //             $variant->stock_status = 'limit-stock';
    //         } else if ($variant->stock >= 10) {
    //             $variant->stock_status = 'in-stock';
    //         } else {
    //             $variant->stock_status = 'out-of-stock';
    //         }

    //         $variant->save();

    //         $status = null;

    //         if ($variant->stock == 0) {
    //             $status = 'sold-out';
    //         } else if ($variant->stock > 0) {
    //             $status = 'available';
    //         }

    //         $variant->restocks()->create([
    //             'quantity' => $variant->stock,
    //             'cost' => $request->variantInputs[$index]['cost'],
    //             'stock_status' => $status,
    //         ]);
    //     }

    //     return response()->json([
    //         'message' => 'Produk berhasil ditambahkan',
    //         'product' => $product,
    //         'status' => 'success',
    //     ]);
    // }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Product $product)
    {
        $validation = Validator::make($request->all(), [
            'sku' => 'required|string|max:255|unique:products,sku,' . $product->id,
            'name' => 'required|string|max:255',
            'category_id' => 'exists:categories,id',
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
        Gate::authorize('delete', $product);

        $product->delete();

        $product->productVariants()->delete();

        return response()->json([
            'message' => 'Produk berhasil dihapus',
            'status' => 'success',
        ]);
    }

    public function productData(Request $request)
    {
        $query = Product::query()->where('store_id', Auth::user()->store->id)->with('category', 'productVariants');

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
        $query = ProductVariant::query()
            ->where('store_id', Auth::user()->store->id)
            ->with('product', 'unit')
            ->orderBy('created_at', 'desc');

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
        $perPage = $request->input('per_page');
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
                'status' => $product->status,
                'quantity' => $product->quantity,
                'unit' => $product->unit->name,
                'variant' => $product->quantity . ' ' . $product->unit->name,
                'stock' => $product->stock,
                'stock_status' => $product->stock_status,
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
        })->with('product:id,sku,name', 'unit:id,name')->get();

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
                'status' => $productVariant->status,
                'quantity' => $productVariant->quantity,
                'unit' => $productVariant->unit->name,
                'variant' => $productVariant->quantity == '1' ? $productVariant->unit->name : $productVariant->quantity . ' ' . $productVariant->unit->name,
                'stock' => $productVariant->stock,
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

    public function exportExcel()
    {
        return Excel::download(new ProductsExport, 'produk-' . now() . '.xlsx');
    }
}
