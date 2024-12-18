<?php

namespace App\Http\Controllers\Admin;

use App\Exports\TransactionsExport;
use Carbon\Carbon;
use Inertia\Inertia;
use App\Models\Transaction;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Maatwebsite\Excel\Facades\Excel;

class TransactionController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        return Inertia::render('Admin/Transactions');
    }

    /**
     * Get all transactions data.
     */
    public function transactionData(Request $request)
    {
        $query = Transaction::query()->where('store_id', Auth::user()->store->id)->with('user')->orderBy('created_at', 'desc');

        // Handle global search
        if ($request->has('search')) {
            $searchTerm = $request->search;
            $query->where(function ($q) use ($searchTerm) {
                $q->where('transaction_code', 'like', "%{$searchTerm}%")
                    ->orWhere('total_price', 'like', "%{$searchTerm}%");
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
        $transactions = $query->paginate($perPage);


        // Calculate 'from' and 'to'
        $from = ($transactions->currentPage() - 1) * $transactions->perPage() + 1;
        $to = min($from + $transactions->count() - 1, $transactions->total());

        $transformedTransactions = $transactions->map(function ($transaction) {
            return [
                'id' => $transaction->id,
                'transaction_code' => $transaction->transaction_code,
                'total_price' => $transaction->total_price,
                'discount' => $transaction->discount,
                'tax' => $transaction->tax,
                'grand_total' => $transaction->grand_total,
                'total_payment' => $transaction->total_payment,
                'total_change' => $transaction->total_change,
                'payment_method' => $transaction->payment_method,
                'user' => $transaction->user->name,
                'created_at' => $transaction->created_at->format('d F Y H:i'),
                'profit' => $transaction->total_profit,
            ];
        });

        return response()->json([
            'data' => $transformedTransactions,
            'meta' => [
                'current_page' => $transactions->currentPage(),
                'last_page' => $transactions->lastPage(),
                'per_page' => $transactions->perPage(),
                'total' => $transactions->total(),
                'from' => $from,
                'to' => $to,
            ],
        ]);
    }

    public function getTransactionChartData(Request $request)
    {
        $range = $request->input('range', 'weekly');

        switch ($range) {
            case 'weekly':
                $data = $this->getWeeklyData();
                break;
            case 'monthly':
                $data = $this->getMonthlyData();
                break;
            case 'yearly':
                $data = $this->getYearlyData();
                break;
            default:
                return response()->json(['error' => 'Invalid range'], 400);
        }

        return response()->json(['transactionlist' => $data]);
    }

    private function getWeeklyData(): array
    {
        $end = Carbon::now()->endOfWeek();
        $start = $end->copy()->subWeeks(3)->startOfWeek();

        return $this->getData($start, $end, 'W');
    }

    private function getMonthlyData(): array
    {
        $end = Carbon::now()->endOfMonth();
        $start = $end->copy()->subMonths(11)->startOfMonth();

        return $this->getData($start, $end, 'F');
    }

    private function getYearlyData(): array
    {
        $end = Carbon::now()->endOfYear();
        $start = $end->copy()->subYears(4)->startOfYear();

        return $this->getData($start, $end, 'Y');
    }

    private function getData(Carbon $start, Carbon $end, string $format): array
    {
        $transactions = Transaction::selectRaw('DATE(created_at) as date, SUM(total_price) as count')
            ->where('store_id', Auth::user()->store->id)
            ->whereBetween('created_at', [$start, $end])
            ->groupBy('date')
            ->orderBy('date')
            ->get();

        $data = [];
        $current = $start->copy();

        while ($current <= $end) {
            $key = $current->format($format);
            $count = $transactions->where('date', $current->toDateString())->first()->count ?? 0;

            if (!isset($data[$key])) {
                $data[$key] = 0;
            }
            $data[$key] += $count;

            $current->addDay();
        }

        return [
            'labels' => array_keys($data),
            'datasets' => [
                [
                    'label' => 'New Transactions',
                    'backgroundColor' => '#f87979',
                    'data' => array_values($data)
                ]
            ]
        ];
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
        return Excel::download(new TransactionsExport($startDate, $endDate), 'transaksi ' . $startDate . ' sd ' . $endDate . '.xlsx');
    }
}
