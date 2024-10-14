<?php

namespace App\Http\Controllers;

use Inertia\Inertia;
use App\Models\Setting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class SettingController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        if ($this->isMobileDevice($request)) {
            return Inertia::render('SettingsMobile');
        }

        return Inertia::render('Settings');
    }

    private function isMobileDevice(Request $request)
    {
        $userAgent = strtolower($request->header('User-Agent'));

        // Deteksi perangkat mobile berdasarkan user-agent string
        return preg_match('/(android|iphone|ipad|ipod|mobile|blackberry|opera mini|opera mobi|iemobile|windows phone|palm|webos)/i', $userAgent);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        if ($request->has('shop_name') || $request->has('shop_address')) {
            Setting::updateOrCreate(
                ['key' => 'shop_name'],
                ['value' => $request->shop_name]
            );

            Setting::updateOrCreate(
                ['key' => 'shop_address'],
                ['value' => $request->shop_address]
            );
        } else {
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

            Setting::updateOrCreate(
                ['key' => $request->key],
                ['value' => $request->value]
            );
        }

        return response()->json([
            'status' => 'success',
            'message' => 'Settings updated successfully'
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Setting $setting)
    {
        //
    }

    public function getSettings()
    {
        $settings = Setting::all();
        $userSettings = Auth::user()->settings;

        return response()->json([
            'status' => 'success',
            'global_settings' => $settings,
            'user_settings' => $userSettings,
        ]);
    }
}
