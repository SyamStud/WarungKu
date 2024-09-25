<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\StockMovement;
use Illuminate\Http\Request;
use Inertia\Inertia;

class StockMovementController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        return Inertia::render('Admin/StockMovements');
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
    public function show(StockMovement $stockMovement)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(StockMovement $stockMovement)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, StockMovement $stockMovement)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(StockMovement $stockMovement)
    {
        //
    }

    /**
     * Get all stocks data.
     */
    public function stockMovementData(Request $request)
    {
        $query = StockMovement::query()->with('productVariant')->orderBy('created_at', 'desc');

        // Handle global search
        if ($request->has('search')) {
            $searchTerm = $request->search;
            $query->where(function ($q) use ($searchTerm) {
                $q->where('quantity', 'like', "%{$searchTerm}%")
                    ->orWhereHas('productVariant', function ($query) use ($searchTerm) {
                        $query->whereHas('product', function ($query) use ($searchTerm) {
                            $query->where('name', 'like', "%{$searchTerm}%");
                        });
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
                'product' => $stock->productVariant->product->name,
                'quantity' => $stock->quantity,
                'type' => $stock->type,
                'reference' => $stock->reference,
                'created_at' => $stock->created_at->format('d/m/Y H:i:s'),
                'variant' => $stock->productVariant->quantity == '1' ? $stock->productVariant->unit->name : $stock->productVariant->quantity . ' ' . $stock->productVariant->unit->name,
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
