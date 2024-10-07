<?php

namespace App\Http\Controllers\Admin;

use Carbon\Carbon;
use Inertia\Inertia;
use App\Models\Transaction;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;

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
    public function show(Transaction $transaction)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Transaction $transaction)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Transaction $transaction)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Transaction $transaction)
    {
        //
    }

    /**
     * Get all transactions data.
     */
    public function transactionData(Request $request)
    {
        $query = Transaction::query()->with('user');

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
                'transaction' => $transaction->transaction_id ? $transaction->transaction->name : '-',
                'created_at' => $transaction->created_at->format('d F Y H:i'),
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
}
