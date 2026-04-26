<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class AdminMiddleware
{
    /**
     * Require authenticated user with admin or staff role.
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (!$request->user() || !$request->user()->isStaff()) {
            if ($request->wantsJson() || $request->header('X-Inertia')) {
                abort(403, 'Bạn không có quyền truy cập.');
            }

            return redirect()->route('admin.login');
        }

        return $next($request);
    }
}
