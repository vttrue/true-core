<?php
/**
 * Created by PhpStorm.
 * User: Deprecator
 * Date: 03.04.2019
 * Time: 14:01
 */

namespace TrueCore\App\Http\Middleware;

use Closure;

/**
 * Class HttpBasicAuthenticate
 *
 * @package TrueCore\App\Http\Middleware
 */
class HttpBasicAuthenticate
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @param  string[]  ...$guards
     * @return mixed
     *
     * @throws \Illuminate\Auth\AuthenticationException
     */
    public function handle($request, Closure $next, ...$guards)
    {
        $configKeys = [
            'exchange' => 'exchange.basicAuthPwd',
            'resize'   => 'resize.basicAuthPwd',
            'external' => 'external.basicAuthPwd'
        ];

        if (count($guards) > 0 && array_key_exists($guards[0], $configKeys)) {

            $basicAuthHeader = $request->header('Authorization', null);

            if ($basicAuthHeader !== null && is_string($basicAuthHeader)) {

                $params = explode(' ', $basicAuthHeader);

                if (count($params) === 2) {
                    if ($params[0] === 'Basic' && $params[1] === config($configKeys[$guards[0]], null)) {
                        return $next($request);
                    }
                }
            }
        }

        throw new \Illuminate\Auth\AuthenticationException('Authorization required');
    }
}