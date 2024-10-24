<?php

namespace App\Http\Controllers\Admin;

use Inertia\Inertia;
use Illuminate\Http\Request;
use App\Models\DebtPaymentItem;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;

class DebtPaymentHistoryController extends Controller
{
    public function index()
    {
        return Inertia::render('Admin/DebtPaymentHistory');
    }

    public function getDebtPaymentHistory(Request $request)
    {

        $query = DebtPaymentItem::query()->where('store_id', Auth::user()->store->id)->with('debtItem', 'debtPayment');

        // Handle global search
        if ($request->has('search')) {
            $searchTerm = $request->search;
            $query->where(function ($q) use ($searchTerm) {
                $q->whereHas('debtItem', function ($q) use ($searchTerm) {
                    $q->WhereHas('transactionItem', function ($q) use ($searchTerm) {
                        $q->whereHas('product', function ($q) use ($searchTerm) {
                            $q->where('name', 'like', "%$searchTerm%");
                        });
                    })->orWhereHas('debt', function ($q) use ($searchTerm) {
                        $q->whereHas('customer', function ($q) use ($searchTerm) {
                            $q->where('name', 'like', "%$searchTerm%");
                        });
                    });
                })->orWhereHas('debtPayment', function ($q) use ($searchTerm) {
                    $q->where('paid_at', 'like', "%$searchTerm%")
                        ->orWhere('payment_code', 'like', "%$searchTerm%");
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
        $debtPayment = $query->paginate($perPage);


        // Calculate 'from' and 'to'
        $from = ($debtPayment->currentPage() - 1) * $debtPayment->perPage() + 1;
        $to = min($from + $debtPayment->count() - 1, $debtPayment->total());

        $transformedDebtPayment = $debtPayment->map(function ($debtPayment) {
            return [
                'id' => $debtPayment->id,
                'payment_code' => $debtPayment->debtPayment->payment_code,
                'customer' => $debtPayment->debtItem->debt->customer->name,
                'product' => $debtPayment->debtItem->transactionItem ? $debtPayment->debtItem->transactionItem->product->name : "TAX",
                'quantity' => $debtPayment->debtItem->transactionItem ? $debtPayment->debtItem->transactionItem->quantity : 1,
                'total_debt' => $debtPayment->debtItem->total_amount,
                'debt_remaining' => $debtPayment->remaining_debt,
                'paid_amount' => $debtPayment->amount,
                'payment_method' => $debtPayment->debtPayment->payment_method,
                'paid_at' => $debtPayment->debtPayment->paid_at,
                'user' => $debtPayment->debtPayment->user->name,
            ];
        });

        return response()->json([
            'data' => $transformedDebtPayment,
            'meta' => [
                'current_page' => $debtPayment->currentPage(),
                'last_page' => $debtPayment->lastPage(),
                'per_page' => $debtPayment->perPage(),
                'total' => $debtPayment->total(),
                'from' => $from,
                'to' => $to,
            ],
        ]);
    }
}
