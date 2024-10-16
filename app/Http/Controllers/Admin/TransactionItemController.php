<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\TransactionItem;
use Illuminate\Http\Request;
use Inertia\Inertia;

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
        $query = TransactionItem::query()->with('transaction', 'product');

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
}
