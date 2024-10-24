<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Inertia\Inertia;

class CustomerController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        return Inertia::render('Admin/Customers');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validation = Validator::make($request->all(), [
            'name' => 'required',
        ]);

        if ($validation->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => $validation->errors(),
            ]);
        }

        Customer::create(array_merge($request->all(), ['store_id' => Auth::user()->store->id]));

        return response()->json([
            'message' => 'Pelanggan berhasil ditambahkan',
            'status' => 'success',
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Customer $customer)
    {
        $customer->update($request->all());

        return response()->json([
            'message' => 'Pelanggan berhasil diubah',
            'status' => 'success',
        ]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Customer $customer)
    {
        $customer->delete();

        return response()->json([
            'message' => 'Pelanggan berhasil dihapus',
            'status' => 'success',
        ]);
    }

    /**
     * Get all customers data.
     */
    public function customerData(Request $request)
    {
        $query = Customer::query()->where('store_id', Auth::user()->store->id)->with('debts');

        // Handle global search
        if ($request->has('search')) {
            $searchTerm = $request->search;
            $query->where(function ($q) use ($searchTerm) {
                $q->where('name', 'like', "%{$searchTerm}%")
                    ->orWhere('phone', 'like', "%{$searchTerm}%");
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
        $customers = $query->paginate($perPage);


        // Calculate 'from' and 'to'
        $from = ($customers->currentPage() - 1) * $customers->perPage() + 1;
        $to = min($from + $customers->count() - 1, $customers->total());

        $transformedCustomers = $customers->map(function ($customer) {
            return [
                'id' => $customer->id,
                'name' => $customer->name,
                'phone' => $customer->phone,
                'address' => $customer->address,
                'total_debt' => $customer->debts->sum('total_amount'),
                'paid_amount' => $customer->debts->sum('paid_amount'),
                'remaining_debt' => $customer->debts->sum('remaining_amount'),
            ];
        });

        return response()->json([
            'data' => $transformedCustomers,
            'meta' => [
                'current_page' => $customers->currentPage(),
                'last_page' => $customers->lastPage(),
                'per_page' => $customers->perPage(),
                'total' => $customers->total(),
                'from' => $from,
                'to' => $to,
            ],
        ]);
    }

    public function getCustomer(Request $request)
    {
        $validation = Validator::make($request->all(), [
            'name' => 'required',
        ]);

        if ($validation->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => $validation->errors(),
            ]);
        }

        $customer = Customer::where('name', $request->name)->where('store_id', Auth::user()->store->id)
            ->with([
                'debts.debtItems' => function ($query) {
                    $query->where('status', '!=', 'paid')
                        ->with([
                            'transactionItem.productVariant.product',
                            'transactionItem.productVariant.unit',
                            'transactionItem.transaction'
                        ]);
                }
            ])
            ->get();

        if (!$customer) {
            return response()->json([
                'status' => 'error',
                'message' => 'Pelanggan tidak ditemukan',
            ]);
        }

        return response()->json([
            'status' => 'success',
            'data' => $customer,
        ]);
    }
}
