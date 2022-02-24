<?php

namespace TrueCore\App\Providers;

use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class ConfigServiceProvider extends ServiceProvider
{
    public function boot()
    {
        //
    }

    public function register()
    {
        $this->mergeConfigFrom(
            __DIR__.'/../../config/auth.php', 'auth'
        );
//        if($this->app->environment() === 'local') {
//            $this->app->register(\Reliese\Coders\CodersServiceProvider::class);
//        }
    }
}
