<?php

namespace App\Http\Controllers\SuperAdmin;

use Inertia\Inertia;
use App\Models\Store;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Barryvdh\Debugbar\Facades\Debugbar;

class StoreApplicationController extends Controller
{
    public function index()
    {
        return Inertia::render('SuperAdmin/StoreApplications');
    }

    public function update(Request $request, Store $store)
    {
        $store = Store::find($request->id);

        if (!$store) {
            return response()->json([
                'status' => 'error',
                'message' => 'Store not found'
            ], 404);
        }

        if ($request->has('reason')) {
            $store->update([
                'status' => 'rejected',
                'reason_of_rejection' => $request->reason,
            ]);

            return response()->json([
                'status' => 'success',
                'message' => 'Toko berhasil ditolak'
            ]);
        } else {
            $store->update(['status' => 'active']);

            $user = $store->users->first();
            
            $user->removeRole('registered-user');
            $user->assignRole('admin');

            return response()->json([
                'status' => 'success',
                'message' => 'Toko berhasil disetujui'
            ]);
        }
    }

    public function destroy(Request $request, $id)
    {
        // Cari store berdasarkan ID secara manual
        $store = Store::find($id);

        if ($store === null) {
            return response()->json([
                'status' => 'error',
                'message' => 'Store not found',
            ], 404);
        }

        $store->delete();

        $request->user()->update([
            'store_id' => null,
        ]);

        return response()->json([
            'status' => 'success',
            'message' => 'Pendaftaran berhasil dibatalkan',
        ]);
    }


    /**
     * Get all stores data.
     */
    public function storeApplicationData(Request $request)
    {
        $query = Store::query()->where('status', 'pending')->with('users');

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
}
