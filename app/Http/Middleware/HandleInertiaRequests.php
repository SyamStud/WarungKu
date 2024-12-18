<?php

namespace App\Http\Middleware;

use Illuminate\Http\Request;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
    /**
     * The root template that is loaded on the first page visit.
     *
     * @var string
     */
    protected $rootView = 'app';

    /**
     * Determine the current asset version.
     */
    public function version(Request $request): string|null
    {
        return parent::version($request);
    }

    /**
     * Define the props that are shared by default.
     *
     * @return array<string, mixed>
     */
    public function share(Request $request): array
    {
        return [
            ...parent::share($request),
            'auth' => [
                'user' => $request->user() ? $request->user()->load(['store', 'roles']) : null,
            ],
            'userSettings' => function () use ($request) {
                if ($request->user()) {
                    // Ambil data settings dari model `UserSetting`
                    return \App\Models\UserSetting::where('user_id', $request->user()->id)
                        ->pluck('value', 'key')
                        ->toArray();
                }
                return [];
            },
            'storeSettings' => function () use ($request) {
                if ($request->user()) {
                    return \App\Models\StoreSetting::where('id', $request->user()->store_id)
                        ->pluck('value', 'key')
                        ->toArray();
                }
                return [];
            },
        ];
    }
}
