<?php

namespace TrueCore\App\Providers;

use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function boot()
    {
        \Validator::extend('phone', function($attribute, $value, $parameters, $validator) {
            return preg_match('%^((8|\+7)[\- ]?)?(\(?\d{3}\)?[\- ]?)?[\d\- ]{7,10}$%i', $value) && strlen($value) >= 10;
        });

        $this->app['router']->matched(function (\Illuminate\Routing\Events\RouteMatched $event) {
            $route = $event->route;

            if (!array_key_exists('guard', $route->getAction())) {
                return;
            }

            $guards = (array)$route->getAction()['guard'];

            $routeGuard = end($guards);

            $this->app['auth']->resolveUsersUsing(function ($guard = null) use ($routeGuard) {
                return $this->app['auth']->guard($routeGuard)->user();
            });

            $this->app['auth']->setDefaultDriver($routeGuard);
        });
    }

    public function register()
    {
//        if($this->app->environment() === 'local') {
//            $this->app->register(\Reliese\Coders\CodersServiceProvider::class);
//        }
    }
}
