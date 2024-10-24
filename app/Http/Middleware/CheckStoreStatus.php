<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Symfony\Component\HttpFoundation\Response;

class CheckStoreStatus
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next)
    {
        // Ambil user yang sedang login
        $user = Auth::user();

        // Cek apakah user memiliki toko
        if ($user->store_id) {
            $store = $user->store;

            // Cek status toko
            if ($store && $store->status !== 'active') {
                return redirect()->route('waiting.approval');
            }
        }

        return $next($request);
    }
}
