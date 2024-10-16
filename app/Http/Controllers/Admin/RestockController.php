<?php

namespace App\Http\Controllers\Admin;

use Inertia\Inertia;
use App\Models\Restock;
use Illuminate\Http\Request;
use App\Models\StockMovement;
use App\Models\ProductVariant;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;
use Barryvdh\Debugbar\Facades\Debugbar;
use Illuminate\Support\Facades\Validator;

class RestockController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        return Inertia::render('Admin/Restocks');
    }

    /**
     * Update the specified resource in storage.
     */
    public function store(Request $request)
    {
        $validation = Validator::make($request->all(), [
            'quantity' => 'required|numeric',
            'product_variant_id' => 'required|exists:product_variants,id',
            'cost' => 'required|numeric',
        ]);

        if ($validation->fails()) {
            return response()->json([
                'message' => $validation->errors(),
                'errors' => $validation->errors(),
                'status' => 'error',
            ]);
        }

        $productVariant = ProductVariant::find($request->product_variant_id);

        $restock = Restock::where('product_variant_id', $productVariant->id)
            ->where('cost', $request->cost)
            ->whereDate('created_at', now()->toDateString())
            ->first();

        if ($restock) {
            $restock->quantity = DB::raw('quantity + ' . $request->quantity);
            $restock->status = 'available';
            $restock->save();
        } else {
            $restock = Restock::create([
            'product_variant_id' => $productVariant->id,
            'cost' => $request->cost,
            'quantity' => $request->quantity,
            'status' => 'available',
            ]);
        }

        $countStockMovement = $request->quantity - $productVariant->stock;

        $stockMovements = new StockMovement();
        $stockMovements->product_variant_id = $productVariant->id;
        $stockMovements->quantity = $countStockMovement;

        if ($stockMovements->quantity > 0) {
            $stockMovements->type = 'in';
            $stockMovements->reference = 'Penambahan Stok';
        } else {
            $stockMovements->type = 'out';
            $stockMovements->reference = 'Pengurangan Stok';
        }

        $stockMovements->save();

        $stock = Restock::where('product_variant_id', $productVariant->id)->sum('quantity');

        $productVariant->stock = $stock;

        // Update status based on new quantity
        if ($productVariant->stock > 10) {
            $productVariant->stock_status = 'in-stock';
        } else if ($productVariant->stock > 0) {
            $productVariant->stock_status = 'limit-stock';
        } else {
            $productVariant->stock_status = 'out-of-stock';
        }

        $productVariant->save();

        return response()->json([
            'message' => 'Stok berhasil diperbarui',
            'stock' => $productVariant->stock,
            'status' => 'success',
        ]);
    }

    /**
     * Get all stocks data.
     */
    public function restockData(Request $request)
    {
        $query = Restock::query()->with('productVariant');

        // Handle global search
        if ($request->has('search')) {
            $searchTerm = $request->search;
            $query->where(function ($q) use ($searchTerm) {
                $q->where('quantity', 'like', "%{$searchTerm}%");
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
        $restocks = $query->paginate($perPage);


        // Calculate 'from' and 'to'
        $from = ($restocks->currentPage() - 1) * $restocks->perPage() + 1;
        $to = min($from + $restocks->count() - 1, $restocks->total());

        $transformedProductVariant = $restocks->map(function ($restock) {
            return [
                'id' => $restock->id,
                'product' => $restock->productVariant->product->name,
                'variant' => $restock->productVariant->quantity == '1' ? $restock->productVariant->unit->name : $restock->productVariant->quantity . ' ' . $restock->productVariant->unit->name,
                'quantity' => $restock->quantity,
                'cost' => $restock->cost,
                'status' => $restock->status,
                'created_at' => $restock->created_at->format('d M Y H:i'),
            ];
        });

        return response()->json([
            'data' => $transformedProductVariant,
            'meta' => [
                'current_page' => $restocks->currentPage(),
                'last_page' => $restocks->lastPage(),
                'per_page' => $restocks->perPage(),
                'total' => $restocks->total(),
                'from' => $from,
                'to' => $to,
            ],
        ]);
    }
}