<?php

namespace App\Http\Controllers;

use Carbon\Carbon;
use Inertia\Inertia;
use App\Models\Restock;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
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
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return Inertia::render('Admin/RestockForm');
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

        $restock = new Restock();
        $restock->product_id = $request->product_id;
        $restock->supplier_id = $request->supplier_id;
        $restock->quantity = $request->quantity;
        $restock->price = $request->price;

        if ($request->has('note')) {
            $restock->note = $request->note;
        }

        $restock->created_by = Auth::user()->id;
        $restock->save();


        return response()->json([
            'status' => 'success',
            'message' => 'Restock added successfully',
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Restock $restock)
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

        $restock->update($request->only('quantity', 'price', 'note'));

        return response()->json([
            'status' => 'success',
            'message' => 'Restock updated successfully',
        ]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Restock $restock)
    {
        $restock->delete();

        return response()->json([
            'status' => 'success',
            'message' => 'Restock deleted successfully',
        ]);
    }

    /**
     * Get all restocks data.
     */
    public function restockData(Request $request)
    {
        $query = Restock::query();

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
        $restocks = $query->paginate($perPage);


        // Calculate 'from' and 'to'
        $from = ($restocks->currentPage() - 1) * $restocks->perPage() + 1;
        $to = min($from + $restocks->count() - 1, $restocks->total());

        $transformedRestocks = $restocks->map(function ($restock) {
            return [
                'id' => $restock->id,
                'product' => $restock->product->name,
                'supplier' => $restock->supplier->name,
                'quantity' => $restock->quantity,
                'price' => $restock->price,
                'note' => $restock->note,
                'created_by' => $restock->createdBy->name,
                'created_at' => $restock->updated_at->format('d M Y H:i'),
            ];
        });

        return response()->json([
            'data' => $transformedRestocks,
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

    public function getRestockChartData(Request $request)
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

        return response()->json(['restocklist' => $data]);
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
        $restocks = Restock::selectRaw('DATE(created_at) as date, SUM(price) as count')
            ->whereBetween('created_at', [$start, $end])
            ->groupBy('date')
            ->orderBy('date')
            ->get();

        $data = [];
        $current = $start->copy();

        while ($current <= $end) {
            $key = $current->format($format);
            $count = $restocks->where('date', $current->toDateString())->first()->count ?? 0;

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
                    'label' => 'New Restocks',
                    'backgroundColor' => '#f87979',
                    'data' => array_values($data)
                ]
            ]
        ];
    }
}
