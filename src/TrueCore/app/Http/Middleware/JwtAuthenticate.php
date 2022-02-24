<?php
/**
 * Created by PhpStorm.
 * User: Deprecator
 * Date: 16.05.2019
 * Time: 19:18
 */

namespace TrueCore\App\Http\Middleware;

use Closure;
use Tymon\JWTAuth\Exceptions\TokenBlacklistedException;
use Tymon\JWTAuth\Exceptions\TokenExpiredException;
use Tymon\JWTAuth\Http\Middleware\Authenticate;

use Illuminate\Http\Request;
use Tymon\JWTAuth\Exceptions\JWTException;
use Symfony\Component\HttpKernel\Exception\UnauthorizedHttpException;

class JwtAuthenticate extends Authenticate
{
    public function handle($request, Closure $next, ...$guards)
    {
        $this->authenticate($request);

        $request->headers->set('Authorization', 'Bearer ' . $this->auth->getToken()->get());
        $request->attributes->add(['token' => $this->auth->getToken()->get()]);

        return $next($request);
    }

    /**
     * Attempt to authenticate a user via the token in the request.
     *
     * @param  \Illuminate\Http\Request  $request
     *
     * @throws \Symfony\Component\HttpKernel\Exception\UnauthorizedHttpException
     *
     * @return void
     */
    public function authenticate(Request $request)
    {
        $this->checkForToken($request);

        try {
            if (! $this->auth->parseToken()->authenticate()) {
                throw new UnauthorizedHttpException('jwt-auth', 'User not found');
            }
        } catch(TokenExpiredException $e) {

            try {
                $token = $this->auth->refresh();

                $request->headers->set('Authorization', 'Bearer ' . $token);

                $this->authenticate($request);

                $this->auth->setToken($token);
            } catch(TokenExpiredException $exception) {
                throw new UnauthorizedHttpException('jwt-auth', 'Invalid token');
            } catch(TokenBlacklistedException $exception) {
                throw new UnauthorizedHttpException('jwt-auth', 'Invalid token');
            }

        } catch (JWTException $e) {
            throw new UnauthorizedHttpException('jwt-auth', $e->getMessage(), $e, $e->getCode());
        }
    }
}
