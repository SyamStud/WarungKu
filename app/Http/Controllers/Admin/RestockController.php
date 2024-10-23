<?php

namespace App\Http\Controllers\Admin;

use App\Exports\RestocksExport;
use Inertia\Inertia;
use App\Models\Restock;
use Illuminate\Http\Request;
use App\Models\StockMovement;
use App\Models\ProductVariant;
use App\Models\TransactionItem;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;
use Maatwebsite\Excel\Facades\Excel;
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
            // Check if there are previous restocks with negative quantity
            $previousRestock = Restock::where('product_variant_id', $productVariant->id)
                ->where('quantity', '<', 0)
                ->orderBy('created_at', 'asc')
                ->first();

            if ($previousRestock) {
                $remainingQuantity = $request->quantity + $previousRestock->quantity;

                if ($remainingQuantity >= 0) {
                    $previousRestock->quantity = 0;
                    $previousRestock->status = 'sold-out';
                    $previousRestock->save();

                    if ($remainingQuantity > 0) {
                        Restock::create([
                            'product_variant_id' => $productVariant->id,
                            'cost' => $request->cost,
                            'quantity' => $remainingQuantity,
                            'status' => 'available',
                        ]);
                    }
                } else {
                    $previousRestock->quantity = $remainingQuantity;
                    $previousRestock->save();
                }
            } else {
                Restock::create([
                    'product_variant_id' => $productVariant->id,
                    'cost' => $request->cost,
                    'quantity' => $request->quantity,
                    'status' => 'available',
                ]);
            }
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

    public function update(Request $request, Restock $restock)
    {
        $validation = Validator::make($request->all(), [
            'product_variant_id' => 'required|exists:product_variants,id',
            'quantity' => 'required|numeric',
            'cost' => 'required|numeric',
        ]);

        if ($validation->fails()) {
            return response()->json([
                'message' => $validation->errors(),
                'errors' => $validation->errors(),
                'status' => 'error',
            ]);
        }

        $restock->update($request->all());

        $productVariant = ProductVariant::find($request->product_variant_id);
        $productVariant->stock = $request->quantity;
        $productVariant->save();

        return response()->json([
            'message' => 'Stok berhasil diperbarui',
            'status' => 'success',
        ]);
    }

    public function audit(Request $request)
    {
        $validation = Validator::make($request->all(), [
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

        $restock = Restock::where('id', $request->id)
            ->first();

        if ($restock) {
            $restock->cost = $request->cost;
            $restock->status = 'audit-completed';
            $restock->save();

            $transactionItem = TransactionItem::where('restock_id', $restock->id)
                ->get();

            foreach ($transactionItem as $item) {
                $profit = $item->discounted_total_price - ($item->quantity * $request->cost);

                $item->profit = $profit;
                $item->save();

                $transaction = $item->transaction;

                $transaction->total_profit += $profit;
                $transaction->save();
            }
        } else {
            return response()->json([
                'status' => 'error',
                'message' => 'Audit stok gagal',
            ]);
        }

        return response()->json([
            'message' => 'Audit stok berhasil',
            'status' => 'success',
        ]);
    }

    /**
     * Get all stocks data.
     */
    public function restockData(Request $request)
    {
        $query = Restock::query()->where('status', '!=', 'sold-out')->with('productVariant')->orderBy('updated_at', 'desc');

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
                'difference' => $restock->difference,
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

    public function exportExcel()
    {
        return Excel::download(new RestocksExport, 'Stok-' . now() . '.xlsx');
    }
}
