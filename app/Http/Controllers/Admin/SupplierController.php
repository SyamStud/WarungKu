<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Supplier;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Inertia\Inertia;

class SupplierController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        return Inertia::render('Admin/Suppliers');
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
        $validation = Validator::make($request->all(), [
            'name' => 'required',
            'address' => 'required',
            'status' => 'required',
            'contact_name' => 'required',
            'contact_phone' => 'required',
        ]);

        if ($validation->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => $validation->errors()->first(),
            ]);
        }

        Supplier::create($request->all());

        return response()->json([
            'status' => 'success',
            'message' => 'Supplier added successfully',
        ]);
    }

    /**
     * Display the specified resource.
     */
    public function show(Supplier $supplier)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Supplier $supplier)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Supplier $supplier)
    {
        $validation = Validator::make($request->all(), [
            'name' => 'required',
            'address' => 'required',
            'status' => 'required',
            'contact_name' => 'required',
            'contact_phone' => 'required',
        ]);

        if ($validation->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => $validation->errors()->first(),
            ]);
        }

        $supplier->update($request->all());

        return response()->json([
            'status' => 'success',
            'message' => 'Supplier updated successfully',
        ]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Supplier $supplier)
    {
        $supplier->delete();

        return response()->json([
            'status' => 'success',
            'message' => 'Supplier deleted successfully',
        ]);
    }

    /**
     * Get all categories data.
     */
    public function supplierData(Request $request)
    {
        $query = Supplier::query();

        // Handle global search
        if ($request->has('search')) {
            $searchTerm = $request->search;
            $query->where(function ($q) use ($searchTerm) {
                $q->where('name', 'like', "%{$searchTerm}%");
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
        $categories = $query->paginate($perPage);


        // Calculate 'from' and 'to'
        $from = ($categories->currentPage() - 1) * $categories->perPage() + 1;
        $to = min($from + $categories->count() - 1, $categories->total());

        $transformedCategories = $categories->map(function ($supplier) {
            return [
                'id' => $supplier->id,
                'name' => $supplier->name,
                'address' => $supplier->address,
                'contact_name' => $supplier->contact_name,
                'contact_phone' => $supplier->contact_phone,
                'contact_email' => $supplier->contact_email,
                'contact_position' => $supplier->contact_position,
                'status' => $supplier->status,
            ];
        });

        return response()->json([
            'data' => $transformedCategories,
            'meta' => [
                'current_page' => $categories->currentPage(),
                'last_page' => $categories->lastPage(),
                'per_page' => $categories->perPage(),
                'total' => $categories->total(),
                'from' => $from,
                'to' => $to,
            ],
        ]);
    }


    public function getByName(Request $request)
    {
        $validation = Validator::make($request->all(), [
            'name' => 'required',
        ]);

        if ($validation->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => $validation->errors()->first(),
            ]);
        }

        $suppliers = Supplier::where('name', 'like', '%' . $request->name . '%')->get();

        if ($suppliers->isEmpty()) {
            return response()->json([
                'status' => 'error',
                'message' => 'No supplier found',
            ]);
        }

        return response()->json([
            'status' => 'success',
            'data' => $suppliers,
        ]);
    }
}
