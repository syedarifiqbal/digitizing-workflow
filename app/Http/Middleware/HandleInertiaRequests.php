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
        return [
            ...parent::share($request),
            'auth' => [
                'user' => $request->user() ? [
                    'id' => $request->user()->id,
                    'name' => $request->user()->name,
                    'email' => $request->user()->email,
                    'tenant_id' => $request->user()->tenant_id,
                    'is_admin' => $request->user()->hasRole('Admin'),
                    'is_manager' => $request->user()->hasRole('Manager'),
                    'is_designer' => $request->user()->hasRole('Designer'),
                    'is_sales' => $request->user()->hasRole('Sales'),
                    'is_client' => $request->user()->hasRole('Client'),
                ] : null,
            ],
            'flash' => [
                'success' => fn () => $request->session()->get('success'),
                'error' => fn () => $request->session()->get('error'),
                'status' => fn () => $request->session()->get('status'),
            ],
            'tenant_settings' => fn () => $request->user()?->tenant ? [
                'date_format' => $request->user()->tenant->getSetting('date_format', 'MM/DD/YYYY'),
            ] : null,
        ];
    }
}
