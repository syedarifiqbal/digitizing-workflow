<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class LoginController extends Controller
{
    public function create(): Response
    {
        $tenantName = null;
        if ($forcedId = config('app.forced_tenant_id')) {
            $tenantName = Tenant::find($forcedId)?->name;
        }

        return Inertia::render('Auth/Login', [
            'tenantName' => $tenantName,
            'registrationEnabled' => true,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $credentials = $request->validate([
            'email' => ['required', 'string', 'email'],
            'password' => ['required', 'string'],
        ]);

        if ($forcedId = config('app.forced_tenant_id')) {
            $user = User::withoutGlobalScopes()
                ->where('tenant_id', $forcedId)
                ->where('email', $credentials['email'])
                ->first();

            if (! $user || ! Hash::check($credentials['password'], $user->password)) {
                throw ValidationException::withMessages([
                    'email' => __('auth.failed'),
                ]);
            }

            Auth::login($user, $request->boolean('remember'));
        } else {
            if (! Auth::attempt($credentials, $request->boolean('remember'))) {
                throw ValidationException::withMessages([
                    'email' => __('auth.failed'),
                ]);
            }
        }

        $request->session()->regenerate();

        return redirect()->intended(route('dashboard'));
    }

    public function destroy(Request $request): RedirectResponse
    {
        Auth::logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }
}
