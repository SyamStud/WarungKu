<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Unit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Inertia\Inertia;

class UnitController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        return Inertia::render('Admin/Units');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validation = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
        ]);

        if ($validation->fails()) {
            return response()->json($validation->errors(), 422);
        }

        Unit::create([
            'name' => strtoupper($request->input('name')),
            'store_id' => Auth::user()->store->id,
        ]);

        return response()->json([
            'status' => 'success',
            'message' => 'Unit berhasil ditambahkan',
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Unit $unit)
    {
        $validation = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
        ]);

        if ($validation->fails()) {
            return response()->json($validation->errors(), 422);
        }

        $unit->update([
            'name' => strtoupper($request->input('name')),
        ]);

        return response()->json([
            'status' => 'success',
            'message' => 'Unit berhasil diubah',
        ]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Unit $unit)
    {
        $unit->delete();

        return response()->json([
            'status' => 'success',
            'message' => 'Unit berhasil dihapus',
        ]);
    }

    /**
     * Get all units data.
     */
    public function unitData(Request $request)
    {
        $query = Unit::query()->where('store_id', Auth::user()->store->id);

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
        $units = $query->paginate($perPage);


        // Calculate 'from' and 'to'
        $from = ($units->currentPage() - 1) * $units->perPage() + 1;
        $to = min($from + $units->count() - 1, $units->total());

        $transformedUnits = $units->map(function ($unit) {
            return [
                'id' => $unit->id,
                'name' => $unit->name,
            ];
        });

        return response()->json([
            'data' => $transformedUnits,
            'meta' => [
                'current_page' => $units->currentPage(),
                'last_page' => $units->lastPage(),
                'per_page' => $units->perPage(),
                'total' => $units->total(),
                'from' => $from,
                'to' => $to,
            ],
        ]);
    }
}
