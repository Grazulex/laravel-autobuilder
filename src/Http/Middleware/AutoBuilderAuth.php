<?php

declare(strict_types=1);

namespace Grazulex\AutoBuilder\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Symfony\Component\HttpFoundation\Response;

class AutoBuilderAuth
{
    public function handle(Request $request, Closure $next): Response
    {
        // Check if user is authenticated
        if (! $request->user()) {
            if ($request->expectsJson()) {
                return response()->json(['message' => 'Unauthenticated.'], 401);
            }

            // Try to redirect to login route if it exists
            if (app('router')->has('login')) {
                return redirect()->guest(route('login'));
            }

            abort(401, 'Unauthenticated. Please log in first.');
        }

        // Check super admin access
        $superAdmins = config('autobuilder.authorization.super_admins', []);
        if (in_array($request->user()->id, $superAdmins)) {
            return $next($request);
        }

        // Check gate authorization
        $gate = config('autobuilder.authorization.gate');
        if ($gate && Gate::has($gate) && Gate::forUser($request->user())->denies($gate)) {
            if ($request->expectsJson()) {
                return response()->json(['message' => 'Unauthorized.'], 403);
            }

            abort(403, 'Unauthorized to access AutoBuilder.');
        }

        return $next($request);
    }
}
