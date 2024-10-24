<?php

namespace App\Http\Controllers;

use App\Models\Store;
use Barryvdh\Debugbar\Facades\Debugbar;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class StoreController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        //
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
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Store $store)
    {
        //
    }
}
