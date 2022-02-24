<?php

namespace TrueCore\App\Http\Middleware;

use Closure;
use Illuminate\Support\Facades\Auth;

class RedirectIfUnauthenticated
{
    /**
     * Handle an incoming request.
     *
     * @param $request
     * @param Closure $next
     * @param null|string $guard
     * @param null|string $route
     * @return \Illuminate\Http\RedirectResponse|\Illuminate\Routing\Redirector|mixed
     */
    public function handle($request, Closure $next, ?string $guard = null, ?string $route = null)
    {
        if ($route) {
            if ($guard) {
                if (!Auth::guard($guard)->check()) {
                    return redirect($route);
                }
            } else {
                if (!Auth::check()) {
                    return redirect($route);
                }
            }
        }

        return $next($request);
    }
}
