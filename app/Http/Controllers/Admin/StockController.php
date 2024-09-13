<?php

namespace App\Http\Controllers\Admin;

use Inertia\Inertia;
use App\Models\Stock;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;
use App\Models\StockMovement;
use Illuminate\Support\Facades\Validator;

class StockController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        return Inertia::render('Admin/Stock');
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
            'quantity' => 'required|numeric',
        ]);

        if ($validation->fails()) {
            return response()->json([
                'message' => $validation->errors(),
                'errors' => $validation->errors(),
                'status' => 'error',
            ]);
        }

        $stock = Stock::updateOrCreate(
            ['product_id' => $request->product_id],
            []
        );

        $stock->quantity += $request->quantity;

        // Update status based on new quantity
        if ($stock->quantity > 10) {
            $stock->status = 'in-stock';
        } else if ($stock->quantity > 0) {
            $stock->status = 'limit-stock';
        } else {
            $stock->status = 'out-of-stock';
        }

        $stock->save();

        $stockMovements = StockMovement::create([
            'product_id' => $request->product_id,
            'quantity' => $request->quantity,
            'type' => 'in',
            'reference' => 'Penambahan Stok',
        ]);

        return response()->json([
            'message' => 'Stok berhasil ditambahkan',
            'stock' => $stock,
            'status' => 'success',
        ]);
    }

    /**
     * Display the specified resource.
     */
    public function show(Stock $stock)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Stock $stock)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Stock $stock)
    {
        $validation = Validator::make($request->all(), [
            'quantity' => 'required|numeric',
        ]);

        if ($validation->fails()) {
            return response()->json([
                'message' => $validation->errors(),
                'errors' => $validation->errors(),
                'status' => 'error',
            ]);
        }

        $countStockMovement = $request->quantity - $stock->quantity;

        $stock->quantity = $request->quantity;

        // Update status based on new quantity
        if ($stock->quantity > 10) {
            $stock->status = 'in-stock';
        } else if ($stock->quantity > 0) {
            $stock->status = 'limit-stock';
        } else {
            $stock->status = 'out-of-stock';
        }

        $stock->save();

        $stockMovements = new StockMovement();
        $stockMovements->product_id = $stock->product_id;
        $stockMovements->quantity = $countStockMovement;

        if ($stockMovements->quantity > 0) {
            $stockMovements->type = 'in';
            $stockMovements->reference = 'Penambahan Stok';
        } else {
            $stockMovements->type = 'out';
            $stockMovements->reference = 'Pengurangan Stok';
        }

        $stockMovements->save();

        return response()->json([
            'message' => 'Stok berhasil diperbarui',
            'stock' => $stock,
            'status' => 'success',
        ]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Stock $stock)
    {
        $stock->delete();

        return response()->json([
            'message' => 'Stok berhasil dihapus',
            'status' => 'success',
        ]);
    }

    /**
     * Get all stocks data.
     */
    public function stockData(Request $request)
    {
        $query = Stock::query()->with('product');

        // Handle global search
        if ($request->has('search')) {
            $searchTerm = $request->search;
            $query->where(function ($q) use ($searchTerm) {
                $q->where('quantity', 'like', "%{$searchTerm}%")
                    ->orWhere('status', 'like', "%{$searchTerm}%")
                    ->orWhereHas('product', function ($q) use ($searchTerm) {
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
        $stocks = $query->paginate($perPage);


        // Calculate 'from' and 'to'
        $from = ($stocks->currentPage() - 1) * $stocks->perPage() + 1;
        $to = min($from + $stocks->count() - 1, $stocks->total());

        $transformedStocks = $stocks->map(function ($stock) {
            return [
                'id' => $stock->id,
                'product' => $stock->product->name,
                'quantity' => $stock->quantity,
                'status' => $stock->status,
            ];
        });

        return response()->json([
            'data' => $transformedStocks,
            'meta' => [
                'current_page' => $stocks->currentPage(),
                'last_page' => $stocks->lastPage(),
                'per_page' => $stocks->perPage(),
                'total' => $stocks->total(),
                'from' => $from,
                'to' => $to,
            ],
        ]);
    }
}
