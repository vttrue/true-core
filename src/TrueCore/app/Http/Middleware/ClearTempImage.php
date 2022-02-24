<?php

namespace TrueCore\App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use \TrueCore\App\Libraries as Lib;

class ClearTempImage
{
    public function handle(Request $request, Closure $next)
    {
        $actionName = $request->route()->getActionName();

        if ($actionName === 'Closure') {
            return $next($request);
        }

        list($controller, $action) = explode('@', $actionName);

        if (in_array($action, ['create', 'edit']) && method_exists($controller, $action)) {
            Lib\TempImage::clear();
        }

        return $next($request);
    }
}
