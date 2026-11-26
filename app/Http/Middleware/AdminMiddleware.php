<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class AdminMiddleware
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (!auth()->check()) {
            return redirect()->route('admin.login');
        }

        if (!auth()->user()->is_admin) {
            abort(403, 'Bu sayfaya erişim yetkiniz bulunmamaktadır.');
        }

        if ($request->routeIs('admin.dashboard') && !auth()->user()->canAccessAdminRoute('admin.dashboard')) {
            return redirect()->route(auth()->user()->adminLandingRoute());
        }

        abort_unless(auth()->user()->canAccessAdminRoute($request->route()?->getName()), 403,
            'Bu işlem için yönetici yetkisi gereklidir.');

        return $next($request);
    }
}
