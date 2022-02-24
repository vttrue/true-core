<?php

namespace TrueCore\App\Providers;

use Illuminate\Support\Facades\Route;
use Illuminate\Foundation\Support\Providers\RouteServiceProvider as ServiceProvider;

class RouteServiceProvider extends ServiceProvider
{
    /**
     * This namespace is applied to your controller routes.
     *
     * In addition, it is set as the URL generator's root namespace.
     *
     * @var string
     */
    protected $namespace = 'TrueCore\App\Http\Controllers';

    /**
     * Define your route model bindings, pattern filters, etc.
     *
     * @return void
     */
    public function boot()
    {
        //

//        parent::boot();
        $this->registerRoutes();
    }

    /**
     * Define the routes for the application.
     *
     * @return void
     */
//    public function map()
//    {
////        $this->mapWebRoutes();
//
//        $this->mapApiRoutes();
//
//        //
//    }

//    /**
//     * Define the "web" routes for the application.
//     *
//     * These routes all receive session state, CSRF protection, etc.
//
//     */
//    protected function mapWebRoutes()
//    {
//        Route::middleware('web')
//            ->namespace($this->namespace)
//            ->group(base_path('routes/web.php'));
//    }

    protected function registerRoutes()
    {
        Route::group([
            'prefix' => 'api',
            'namespace' => $this->namespace,
            'middleware' => 'api'
        ], function () {
            $this->loadRoutesFrom(__DIR__.'/../../routes/api.php');
        });
        Route::group([
            'prefix' => 'api',
            'namespace' => $this->namespace,
            'middleware' => 'api'
        ], function () {
            $this->loadRoutesFrom(__DIR__.'/../../routes/frontend.php');
        });
        Route::group([
            'prefix' => 'api',
            'namespace' => $this->namespace,
            'middleware' => 'resize:resize'
        ], function () {
            $this->loadRoutesFrom(__DIR__.'/../../routes/image.php');
        });
    }

//    /**
//     * Define the "api" routes for the application.
//     *
//     * These routes are typically stateless.
//     */
//    protected function mapApiRoutes()
//    {
////        dd(1);
//        Route::prefix('api')
//            ->middleware('api')
//            ->namespace($this->namespace)
//            ->group(base_path('routes/api.php'));
//
//        Route::prefix('api')
//            ->middleware('api')
//            ->namespace($this->namespace)
//            ->group(base_path('routes/frontend.php'));
//
////        Route::prefix('api')
////            ->middleware('api')
////            ->namespace($this->namespace)
////            ->group(base_path('routes/account.php'));
//
////        Route::prefix('api')
////            ->middleware('api')
////            ->namespace('App\Modules\Exchange\Http\Controllers')
////            ->group(base_path('routes/exchange.php'));
//    }
}
