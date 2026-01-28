<?php

namespace App\Http\Middleware;

use Illuminate\Http\Request;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
    /**
     * The root template that's loaded on the first page visit.
     *
     * @see https://inertiajs.com/server-side-setup#root-template
     *
     * @var string
     */
    protected $rootView = 'app';

    /**
     * Determines the current asset version.
     *
     * @see https://inertiajs.com/asset-versioning
     */
    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    /**
     * Define the props that are shared by default.
     *
     * @see https://inertiajs.com/shared-data
     *
     * @return array<string, mixed>
     */
    public function share(Request $request): array
    {
        $user = $request->user();

        return [
            ...parent::share($request),
            'auth' => [
                'user' => $user ? [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'tenant_id' => $user->tenant_id,
                    'is_admin' => $user->hasRole('Admin'),
                    'is_manager' => $user->hasRole('Manager'),
                    'is_designer' => $user->hasRole('Designer'),
                    'is_sales' => $user->hasRole('Sales'),
                    'is_client' => $user->hasRole('Client') || ! is_null($user->client_id),
                ] : null,
            ],
            'flash' => [
                'success' => fn () => $request->session()->get('success'),
                'error' => fn () => $request->session()->get('error'),
                'status' => fn () => $request->session()->get('status'),
                'api_key' => fn () => $request->session()->get('api_key_plain'),
            ],
            'tenant_settings' => fn () => $request->user()?->tenant ? [
                'date_format' => $request->user()->tenant->getSetting('date_format', 'MM/DD/YYYY'),
            ] : null,
        ];
    }
}
