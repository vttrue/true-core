<?php

namespace TrueCore\App\Http;

use Illuminate\Foundation\Http\Kernel as HttpKernel;

class Kernel extends HttpKernel
{
    protected $middleware = [
        \Illuminate\Foundation\Http\Middleware\CheckForMaintenanceMode::class,
        \Illuminate\Foundation\Http\Middleware\ValidatePostSize::class,
        \TrueCore\App\Http\Middleware\TrimStrings::class,
        \Illuminate\Foundation\Http\Middleware\ConvertEmptyStringsToNull::class,
        \TrueCore\App\Http\Middleware\TrustProxies::class,
        //\Barryvdh\Cors\HandleCors::class,
    ];

    protected $middlewareGroups = [
        'web' => [
            \TrueCore\App\Http\Middleware\EncryptCookies::class,
            \Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse::class,
            \Illuminate\Session\Middleware\StartSession::class,
            //\Illuminate\Session\Middleware\AuthenticateSession::class,
            \Illuminate\View\Middleware\ShareErrorsFromSession::class,
            // \TrueCore\App\Http\Middleware\VerifyCsrfToken::class,
            \Illuminate\Routing\Middleware\SubstituteBindings::class
        ],

        'api' => [
            'throttle:1000,1',
            //'bindings',
            \TrueCore\App\Http\Middleware\EncryptCookies::class,
            \Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse::class,
            \Illuminate\Session\Middleware\StartSession::class,
            // \Illuminate\Session\Middleware\AuthenticateSession::class,
            \Illuminate\View\Middleware\ShareErrorsFromSession::class,
            // \TrueCore\App\Http\Middleware\VerifyCsrfToken::class,
            \Illuminate\Routing\Middleware\SubstituteBindings::class,
        ],
    ];

    protected $routeMiddleware = [
        //'language'         => \TrueCore\App\Http\Middleware\Language::class,
        'auth'             => \TrueCore\App\Http\Middleware\Authenticate::class,
        'auth.basic'       => \Illuminate\Auth\Middleware\AuthenticateWithBasicAuth::class,

        'auth.http.basic'  => \TrueCore\App\Http\Middleware\HttpBasicAuthenticate::class,

        // Проверка прав роли пользователя
        'permission'       => \TrueCore\App\Http\Middleware\Permission::class,

        'bindings' => \Illuminate\Routing\Middleware\SubstituteBindings::class,
        'guest'    => \TrueCore\App\Http\Middleware\RedirectIfAuthenticated::class,
        'throttle' => \Illuminate\Routing\Middleware\ThrottleRequests::class,

        'clearTempImage' => \TrueCore\App\Http\Middleware\ClearTempImage::class,

        'checkAuth'    => \TrueCore\App\Http\Middleware\RedirectIfUnauthenticated::class,

        'modifiable' => \TrueCore\App\Http\Middleware\IfModifiedSince::class,

        'resize'     => \TrueCore\App\Http\Middleware\HttpBasicAuthenticate::class

    ];

    /**
     * The priority-sorted list of middleware.
     *
     * This forces non-global middleware to always be in the given order.
     *
     * @var array
     */
    protected $middlewarePriority = [
        \Illuminate\Session\Middleware\StartSession::class,
        \Illuminate\View\Middleware\ShareErrorsFromSession::class,
        \TrueCore\App\Http\Middleware\Authenticate::class,
        \Illuminate\Session\Middleware\AuthenticateSession::class,
        \Illuminate\Routing\Middleware\SubstituteBindings::class,
        \Illuminate\Auth\Middleware\Authorize::class,
    ];
}
