<?php

namespace App\Http\Controllers\Admin;

use App\Exports\StockMovementsExport;
use Inertia\Inertia;
use Illuminate\Http\Request;
use App\Models\StockMovement;
use App\Http\Controllers\Controller;
use Maatwebsite\Excel\Facades\Excel;

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

    public function exportExcel()
    {
        // Tangkap tanggal dari query string
        $startDate = request('start_date');
        $endDate = request('end_date');

        // Pastikan tanggal diberikan
        if (!$startDate || !$endDate) {
            return response()->json(['error' => 'Start date and end date are required'], 400);
        }

        // Panggil export menggunakan Maatwebsite Excel atau metode lainnya
        return Excel::download(new StockMovementsExport($startDate, $endDate), 'stock-movements.xlsx');
    }
}
