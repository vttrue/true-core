<?php

namespace TrueCore\App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

class Permission
{
    public function handle(Request $request, Closure $next)
    {
        $actionName = $request->route()->getActionName();

        if ($actionName === 'Closure') {
            return $next($request);
        }

        list($controller, $action) = explode('@', $actionName);

        if (!_getCurrentUser('api')->hasPermission($controller, $action)) {
            abort(403);
        }

        return $next($request);
    }
}
