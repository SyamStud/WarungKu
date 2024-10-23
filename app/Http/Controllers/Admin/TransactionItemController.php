<?php

namespace App\Http\Controllers\Admin;

use App\Exports\TransactionItemsExport;
use Inertia\Inertia;
use Illuminate\Http\Request;
use App\Models\TransactionItem;
use App\Http\Controllers\Controller;
use Maatwebsite\Excel\Facades\Excel;

class TransactionItemController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        return Inertia::render('Admin/TransactionItems');
    }

    /**
     * Get all transactionItems data.
     */
    public function transactionItemData(Request $request)
    {
        $query = TransactionItem::query()->with('transaction', 'product')->orderBy('created_at', 'desc');

        // Handle global search
        if ($request->has('search')) {
            $searchTerm = $request->search;
            $query->where(function ($q) use ($searchTerm) {
                $q->Where('total_price', 'like', "%{$searchTerm}%")
                    ->orWhereHas('product', function ($q) use ($searchTerm) {
                        $q->where('name', 'like', "%{$searchTerm}%");
                    })
                    ->orWhereHas('transaction', function ($q) use ($searchTerm) {
                        $q->where('transaction_code', 'like', "%{$searchTerm}%");
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
        $transactionItems = $query->paginate($perPage);

        // Calculate 'from' and 'to'
        $from = ($transactionItems->currentPage() - 1) * $transactionItems->perPage() + 1;
        $to = min($from + $transactionItems->count() - 1, $transactionItems->total());

        $transformedTransactionItems = $transactionItems->map(function ($transactionItem) {
            return [
                'id' => $transactionItem->id,
                'transaction' => $transactionItem->transaction->transaction_code,
                'product' => $transactionItem->product->name,
                'variant' => $transactionItem->productVariant ? ($transactionItem->productVariant->quantity == 1 ? $transactionItem->productVariant->unit->name : $transactionItem->productVariant->quantity . ' - ' . $transactionItem->productVariant->unit->name) : '',
                'quantity' => $transactionItem->quantity,
                'price' => $transactionItem->price,
                'discount' => $transactionItem->discount,
                'total_price' => $transactionItem->total_price,
                'discounted_total_price' => $transactionItem->discounted_total_price,
                'profit' => $transactionItem->profit,
            ];
        });

        return response()->json([
            'data' => $transformedTransactionItems,
            'meta' => [
                'current_page' => $transactionItems->currentPage(),
                'last_page' => $transactionItems->lastPage(),
                'per_page' => $transactionItems->perPage(),
                'total' => $transactionItems->total(),
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
        return Excel::download(new TransactionItemsExport($startDate, $endDate), 'transaksi-' . now() . '.xlsx');
    }
}
