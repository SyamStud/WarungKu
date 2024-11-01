<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Exports\StoresExport;
use Inertia\Inertia;
use App\Models\Store;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Support\Facades\Validator;

class StoreController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        return Inertia::render('SuperAdmin/Stores');
    }

    public function applicationIndex()
    {
        return Inertia::render('SuperAdmin/StoreApplications');
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
            'nik' => 'required|string|max:255',
            'owner_phone' => 'required|string|max:255',
            'owner_address' => 'required|string|max:255',
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255',
            'address' => 'required|string|max:255',
            'phone' => 'required|string|max:255',
            'website' => 'required|string|max:255',
        ]);

        if ($validation->fails()) {
            return response()->json([
                'message' => 'The given data was invalid.',
                'errors' => $validation->errors(),
            ], 422);
        }

        $store = Store::create([
            'name' => $request->name,
            'address' => $request->address,
            'phone' => $request->phone,
            'email' => $request->email,
            'website' => $request->website,
        ]);

        $user = $request->user();

        $user->update([
            'nik' => $request->nik,
            'phone' => $request->owner_phone,
            'address' => $request->owner_address,
            'store_id' => $store->id,
        ]);

        return redirect()->intended(route('dashboard', absolute: false) . '?store=1');
    }

    /**
     * Display the specified resource.
     */
    public function show(Store $store)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Store $store)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Store $store)
    {
        $store->update($request->all());

        return response()->json([
            'status' => 'success',
            'message' => 'Toko Berhasil Diupdate',
        ]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Store $store)
    {
        //
    }

    /**
     * Get all stores data.
     */
    public function storeData(Request $request)
    {
        $query = Store::query()->where('status', 'active')->orWhere('status', 'inactive')->with('users');

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
        $stores = $query->paginate($perPage);


        // Calculate 'from' and 'to'
        $from = ($stores->currentPage() - 1) * $stores->perPage() + 1;
        $to = min($from + $stores->count() - 1, $stores->total());

        $transformedStores = $stores->map(function ($store) {
            return [
                'id' => $store->id,
                'name' => $store->name,
                'address' => $store->address,
                'phone' => $store->phone,
                'email' => $store->email,
                'website' => $store->website,
                'status' => $store->status,
                'owner' => $store->users()->role('admin')->first()->email ?? $store->users()->role('registered-user')->first()->email ?? 'N/A',
                'created_at' => $store->created_at->format('d M Y H:i'),
            ];
        });

        return response()->json([
            'data' => $transformedStores,
            'meta' => [
                'current_page' => $stores->currentPage(),
                'last_page' => $stores->lastPage(),
                'per_page' => $stores->perPage(),
                'total' => $stores->total(),
                'from' => $from,
                'to' => $to,
            ],
        ]);
    }

    public function exportExcel()
    {
        return Excel::download(new StoresExport(), 'toko-' . now() . '.xlsx');
    }
}
