<?php

namespace App\Http\Controllers\Admin;

use Carbon\Carbon;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Models\Purchase;
use App\Models\Transaction;
use Barryvdh\Debugbar\Facades\Debugbar;
use Barryvdh\Debugbar\Twig\Extension\Debug;
use Inertia\Inertia;
use Spatie\Browsershot\Browsershot;

class ReportController extends Controller
{
    public function transactionIndex()
    {
        return Inertia::render('Admin/Reports/Transaction');
    }

    public function purchaseIndex()
    {
        return Inertia::render('Admin/Reports/Purchase');
    }

    /**
     * Get monthly transaction data.
     */
    public function getMonthlytransaction($year, $month)
    {
        $startDate = Carbon::create($year, $month, 1)->startOfMonth();
        $endDate = $startDate->copy()->endOfMonth();

        $transactions = Transaction::whereBetween('created_at', [$startDate, $endDate])
            ->get()
            ->groupBy(function ($item) {
                return $item->created_at->format('Y-m-d');
            })
            ->map(function ($group) {
                return $group->sum('total_price');
            });

        return response()->json($transactions);
    }

    /**
     * Get monthly purchase data.
     */
    public function getMonthlypurchase($year, $month)
    {
        $startDate = Carbon::create($year, $month, 1)->startOfMonth();
        $endDate = $startDate->copy()->endOfMonth();

        $purchases = Purchase::whereBetween('created_at', [$startDate, $endDate])
            ->get()
            ->groupBy(function ($item) {
                return $item->created_at->format('Y-m-d');
            })
            ->map(function ($group) {
                return $group->sum('price');
            });

        return response()->json($purchases);
    }

    public function savetransactionReport(Request $request)
    {
        $htmlContent = $request->input('html');

        Browsershot::html($htmlContent)
            ->format('A4')
            ->margins(10, 10, 10, 10)
            ->showBackground()
            ->save('transaction.pdf');

        return response()->json(['message' => 'PDF generated successfully']);
    }
}
