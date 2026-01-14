<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureEmailIsVerified
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user) {
            return $next($request);
        }

        if ($user->isAdmin()) {
            return $next($request);
        }

        $requiresVerification = $user->tenant?->getSetting('email_verification_required', true);

        if (! $requiresVerification || $user->hasVerifiedEmail()) {
            return $next($request);
        }

        if ($request->expectsJson()) {
            abort(403, 'Your email address is not verified.');
        }

        return redirect()->route('verification.notice');
    }
}
