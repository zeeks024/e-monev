<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class RedirectIfAuthenticated
{
    public function handle(Request $request, Closure $next, string ...$guards): Response
    {
        $guards = empty($guards) ? [null] : $guards;

        foreach ($guards as $guard) {
            if (Auth::guard($guard)->check()) {
                // Jika guard adalah 'admin' dan admin sudah login,
                // arahkan ke dashboard admin.
                if ($guard === 'admin') {
                    return redirect(route('admin.dashboard'));
                }

                // Jika tidak, arahkan ke dashboard user biasa (dinas).
                return redirect(route('dashboard'));
            }
        }

        return $next($request);
    }
}
