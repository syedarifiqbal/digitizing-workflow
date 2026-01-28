<?php

namespace App\Http\Middleware;

use App\Models\Tenant;
use Closure;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ValidateApiKey
{
    public function handle(Request $request, Closure $next): JsonResponse|\Symfony\Component\HttpFoundation\Response
    {
        $token = $request->bearerToken();

        if (! $token) {
            return response()->json([
                'message' => 'Missing API key.',
            ], 401);
        }

        $hash = hash('sha256', $token);

        $tenant = Tenant::query()
            ->where('settings->api_enabled', true)
            ->where('settings->api_key_hash', $hash)
            ->first();

        if (! $tenant) {
            return response()->json([
                'message' => 'Invalid API key.',
            ], 401);
        }

        // Downstream controllers/actions can grab the resolved tenant from the request
        $request->attributes->set('apiTenant', $tenant);

        return $next($request);
    }
}
