<?php

namespace App\Http\Controllers\Pos;

use Inertia\Inertia;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Models\Transaction;

class PosController extends Controller
{
    public function index(Request $request)
    {
        if ($this->isMobileDevice($request)) {
            return Inertia::render('Pos/PosMobile');
        }
        return Inertia::render('Pos/Pos');
    }

    private function isMobileDevice(Request $request)
    {
        $userAgent = strtolower($request->header('User-Agent'));

        // Deteksi perangkat mobile berdasarkan user-agent string
        return preg_match('/(android|iphone|ipad|ipod|mobile|blackberry|opera mini|opera mobi|iemobile|windows phone|palm|webos)/i', $userAgent);
    }

    public function countTransaction()
    {
        $total = Transaction::count();
        $todayCount = Transaction::whereDate('created_at', now()->toDateString())->count();
        return response()->json(['total' => $total, 'today' => $todayCount]);
    }
}
