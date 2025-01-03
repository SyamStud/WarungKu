<?php

namespace App\Http\Controllers\Admin;

use App\Exports\DebtItemsExport;
use Inertia\Inertia;
use App\Models\DebtItem;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Maatwebsite\Excel\Facades\Excel;
use Barryvdh\Debugbar\Facades\Debugbar;

class DebtItemController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        return Inertia::render('Admin/DebtItems');
    }

    /**
     * Get all debtItems data.
     */
    public function debtItemData(Request $request)
    {
        $query = DebtItem::query()->where('store_id', Auth::user()->store->id)->with('debt', 'transactionItem');

        // Handle global search
        if ($request->has('search')) {
            $searchTerm = $request->search;
            $query->where(function ($q) use ($searchTerm) {
                $q->where('paid_amount', 'like', "%{$searchTerm}%")
                    ->orWhere('remaining_amount', 'like', "%{$searchTerm}%")
                    ->orWhere('status', 'like', "%{$searchTerm}%")
                    ->orWhere('last_payment_at', 'like', "%{$searchTerm}%")
                    ->orWhere('settled_at', 'like', "%{$searchTerm}%")
                    ->orWhereHas('debt', function ($q) use ($searchTerm) {
                        $q->whereHas('customer', function ($q) use ($searchTerm) {
                            $q->where('name', 'like', "%{$searchTerm}%");
                        });
                    })
                    ->orWhereHas('transactionItem', function ($q) use ($searchTerm) {
                        $q->whereHas('transaction', function ($q) use ($searchTerm) {
                            $q->where('transaction_code', 'like', "%{$searchTerm}%");
                        })->orWhereHas('product', function ($q) use ($searchTerm) {
                            $q->where('name', 'like', "%{$searchTerm}%");
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
        $debtItems = $query->paginate($perPage);

        $firstDebtItem = $query->first();

        // Calculate 'from' and 'to'
        $from = ($debtItems->currentPage() - 1) * $debtItems->perPage() + 1;
        $to = min($from + $debtItems->count() - 1, $debtItems->total());

        $transformedDebtItems = $debtItems->map(function ($debtItem) use ($firstDebtItem) {
            return [
                'id' => $debtItem->id,
                'customer' => $debtItem->debt->customer->name,
                'transaction' => $debtItem->transactionItem ? $debtItem->transactionItem->transaction->transaction_code : $firstDebtItem->transactionItem->transaction->transaction_code,
                'product' => $debtItem->transactionItem ? $debtItem->transactionItem->product->name : 'TAX',
                'quantity' => $debtItem->transactionItem ? $debtItem->transactionItem->quantity : 1,
                'total_amount' => $debtItem->transactionItem ? $debtItem->transactionItem->discounted_total_price : $debtItem->total_amount,
                'paid_amount' => $debtItem->paid_amount,
                'remaining_amount' => $debtItem->remaining_amount,
                'status' => $debtItem->status,
                'last_payment_at' => $debtItem->last_payment_at,
                'settled_at' => $debtItem->settled_at,
            ];
        });

        return response()->json([
            'data' => $transformedDebtItems,
            'meta' => [
                'current_page' => $debtItems->currentPage(),
                'last_page' => $debtItems->lastPage(),
                'per_page' => $debtItems->perPage(),
                'total' => $debtItems->total(),
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

        return Excel::download(new DebtItemsExport($startDate, $endDate), 'item-hutang-' . now() . '.xlsx');
    }
}
