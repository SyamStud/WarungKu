<?php

namespace App\Http\Controllers;

use Carbon\Carbon;
use Inertia\Inertia;
use App\Models\Purchase;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;


class PurchaseController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        return Inertia::render('Admin/Purchases');
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return Inertia::render('Admin/PurchaseForm');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validation = Validator::make($request->all(), [
            'product_id' => 'required',
            'supplier_id' => 'required',
            'quantity' => 'required',
            'price' => 'required',
        ]);

        if ($validation->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => $validation->errors()->first(),
            ]);
        }

        $purchase = new Purchase();
        $purchase->product_id = $request->product_id;
        $purchase->supplier_id = $request->supplier_id;
        $purchase->quantity = $request->quantity;
        $purchase->price = $request->price;

        if ($request->has('note')) {
            $purchase->note = $request->note;
        }

        $purchase->user_id = Auth::user()->id;
        $purchase->store_id = Auth::user()->store->id;
        $purchase->save();


        return response()->json([
            'status' => 'success',
            'message' => 'Purchase added successfully',
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Purchase $purchase)
    {
        $validation = Validator::make($request->all(), [
            'quantity' => 'required',
            'price' => 'required',
        ]);

        if ($validation->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => $validation->errors()->first(),
            ]);
        }

        $purchase->update($request->only('quantity', 'price', 'note'));

        return response()->json([
            'status' => 'success',
            'message' => 'Purchase updated successfully',
        ]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Purchase $purchase)
    {
        $purchase->delete();

        return response()->json([
            'status' => 'success',
            'message' => 'Purchase deleted successfully',
        ]);
    }

    /**
     * Get all purchases data.
     */
    public function purchaseData(Request $request)
    {
        $query = Purchase::query()->where('store_id', Auth::user()->store->id)
            ->with('product', 'supplier', 'user');

        // Handle global search
        if ($request->has('search')) {
            $searchTerm = $request->search;
            $query->where(function ($q) use ($searchTerm) {
                $q->whereHas('product', function ($query) use ($searchTerm) {
                    $query->where('name', 'like', '%' . $searchTerm . '%');
                })->orWhereHas('supplier', function ($query) use ($searchTerm) {
                    $query->where('name', 'like', '%' . $searchTerm . '%');
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
        $purchases = $query->paginate($perPage);


        // Calculate 'from' and 'to'
        $from = ($purchases->currentPage() - 1) * $purchases->perPage() + 1;
        $to = min($from + $purchases->count() - 1, $purchases->total());

        $transformedPurchases = $purchases->map(function ($purchase) {
            return [
                'id' => $purchase->id,
                'product' => $purchase->product->name,
                'supplier' => $purchase->supplier->name,
                'quantity' => $purchase->quantity,
                'price' => $purchase->price,
                'note' => $purchase->note,
                'user_id' => $purchase->user->name,
                'created_at' => $purchase->updated_at->format('d M Y H:i'),
            ];
        });

        return response()->json([
            'data' => $transformedPurchases,
            'meta' => [
                'current_page' => $purchases->currentPage(),
                'last_page' => $purchases->lastPage(),
                'per_page' => $purchases->perPage(),
                'total' => $purchases->total(),
                'from' => $from,
                'to' => $to,
            ],
        ]);
    }

    public function getPurchaseChartData(Request $request)
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

        return response()->json(['purchaselist' => $data]);
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
        $purchases = Purchase::selectRaw('DATE(created_at) as date, SUM(price) as count')
            ->whereBetween('created_at', [$start, $end])
            ->where('store_id', Auth::user()->store->id)
            ->groupBy('date')
            ->orderBy('date')
            ->get();

        $data = [];
        $current = $start->copy();

        while ($current <= $end) {
            $key = $current->format($format);
            $count = $purchases->where('date', $current->toDateString())->first()->count ?? 0;

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
                    'label' => 'New Purchases',
                    'backgroundColor' => '#f87979',
                    'data' => array_values($data)
                ]
            ]
        ];
    }
}
