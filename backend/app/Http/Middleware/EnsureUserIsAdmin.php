<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Admin gate for the website dashboard (M7.4). The dashboard exposes donation
 * settings, district/locality names and DPDP requests, so every /admin route
 * sits behind session auth plus this role check.
 */
class EnsureUserIsAdmin
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user === null) {
            // Browser requests go to the sign-in form; API clients get 401.
            if ($request->expectsJson()) {
                return response()->json(['message' => 'Authentication required.'], 401);
            }

            return redirect()->guest(route('admin.login'));
        }

        if ($user->role !== 'admin') {
            if ($request->expectsJson()) {
                return response()->json(['message' => 'Administrator access only.'], 403);
            }

            abort(403, 'Administrator access only.');
        }

        return $next($request);
    }
}