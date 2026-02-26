<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\Client;
use App\Models\Role;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rules\Password;
use Inertia\Inertia;
use Inertia\Response;

class ClientRegisterController extends Controller
{
    public function create(): Response
    {
        return Inertia::render('Auth/ClientRegister');
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name'                  => ['required', 'string', 'max:255'],
            'email'                 => ['required', 'string', 'email', 'max:255', 'unique:users'],
            'password'              => ['required', 'confirmed', Password::defaults()],
        ]);

        $tenantId = config('app.forced_tenant_id');
        $tenant   = Tenant::findOrFail($tenantId);

        $client = Client::create([
            'tenant_id' => $tenant->id,
            'name'      => $validated['name'],
            'email'     => $validated['email'],
            'is_active' => true,
        ]);

        $user = User::create([
            'tenant_id' => $tenant->id,
            'client_id' => $client->id,
            'name'      => $validated['name'],
            'email'     => $validated['email'],
            'password'  => $validated['password'],
        ]);

        // Resolve role by object to avoid global scope issues (user not logged in yet)
        $clientRole = Role::withoutGlobalScopes()
            ->where('tenant_id', $tenant->id)
            ->where('name', 'Client')
            ->first();

        if ($clientRole) {
            $user->assignRole($clientRole);
        }

        event(new Registered($user));

        Auth::login($user);

        return redirect()->route('client.dashboard');
    }
}
