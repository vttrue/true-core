<?php

namespace TrueCore\App\Providers;

use Illuminate\Support\ServiceProvider;

class MigrationsServiceProvider extends ServiceProvider
{
    public function boot()
    {
        $this->loadMigrationsFrom(__DIR__.'/../../database/migrations');
    }
}
