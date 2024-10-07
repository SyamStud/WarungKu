<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckForMobileDevice
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next)
    {
        // Deteksi perangkat menggunakan user-agent
        if ($this->isMobileDevice($request)) {
            // Redirect atau tampilkan pesan bahwa halaman tidak tersedia di perangkat mobile
            return redirect('/not-available-mobile');
        }

        return $next($request);
    }

    private function isMobileDevice(Request $request)
    {
        $userAgent = strtolower($request->header('User-Agent'));

        // Deteksi perangkat mobile berdasarkan user-agent string
        return preg_match('/(android|iphone|ipad|ipod|mobile|blackberry|opera mini|opera mobi|iemobile|windows phone|palm|webos)/i', $userAgent);
    }
}
