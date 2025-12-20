<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckRole
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        if (!$request->user()) {
            return redirect()->route('login');
        }

        // If no roles specified or user's role is in the allowed roles
        if (empty($roles) || in_array($request->user()->role, $roles)) {
            return $next($request);
        }

        // If user is karyawan trying to access admin-only routes, redirect to orders page
        if ($request->user()->isKaryawan()) {
            return redirect()->route('admin.bookings.orders')
                ->with('error', 'Anda tidak memiliki akses ke halaman ini.');
        }

        abort(403, 'Unauthorized');
    }
}
