<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use Inertia\Response;

class AuthenticatedSessionController extends Controller
{
    /**
     * Display the login view.
     */
    public function create(): Response
    {
        return Inertia::render('Auth/Login', [
            'canResetPassword' => Route::has('password.request'),
            'status' => session('status'),
        ]);
    }

    /**
     * Handle an incoming authentication request.
     */
    public function store(LoginRequest $request): RedirectResponse
    {
        $request->authenticate();

        $request->session()->regenerate();


        if (Auth::user()->hasRole('super-admin')) {
            return redirect()->intended(route('dashboard.superadmin', [], false));
        } elseif (Auth::user()->hasRole('cashier')) {
            return redirect()->intended('/pos');
        } elseif (Auth::user()->hasRole('admin')) {
            return redirect()->intended(route('dashboard', [], false));
        } else if (Auth::user()->hasRole('input-staff')) {
            return redirect()->intended(route('products.index', [], false));
        } else {
            return redirect()->intended(route('noStore', [], false)); // Default route if no role matches
        }
    }

    /**
     * Destroy an authenticated session.
     */
    public function destroy(Request $request): RedirectResponse
    {
        Auth::guard('web')->logout();

        $request->session()->invalidate();

        $request->session()->regenerateToken();

        return redirect('/');
    }
}
