<?php

namespace App\Http\Controllers;

use App\Models\StoreSetting;
use Barryvdh\Debugbar\Facades\Debugbar;
use Barryvdh\Debugbar\Twig\Extension\Debug;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Inertia\Inertia;

class StoreSettingController extends Controller
{
    public function index()
    {
        return Inertia::render('Admin/StoreSettings');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'key' => 'required',
            'value' => 'required',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => $validator->errors()->first()
            ]);
        }

        StoreSetting::updateOrCreate(
            ['store_id' => $request->store_id, 'key' => $request->key],
            ['value' => $request->value]
        );

        return response()->json([
            'status' => 'success',
            'message' => 'Setting has been saved.'
        ]);
    }

    /**
     * Display the specified resource.
     */
    public function show(StoreSetting $storeSetting)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(StoreSetting $storeSetting)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, StoreSetting $storeSetting)
    {
        $storeSetting->store_name = $request->store_name;
        $storeSetting->store_address = $request->store_address;
        $storeSetting->save();

        return response()->json([
            'status' => 'success',
            'message' => 'Pengaturan berhasil diperbarui'
        ]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(StoreSetting $storeSetting)
    {
        //
    }
}
