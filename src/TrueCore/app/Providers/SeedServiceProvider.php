<?php

namespace TrueCore\App\Providers;

use Illuminate\Console\Events\CommandFinished;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Request;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Str;
use Symfony\Component\Console\Output\ConsoleOutput;

class SeedServiceProvider extends ServiceProvider
{
    protected $seedsPath = '/../../database/seeds';

    /**
     * Bootstrap services.
     *
     * @return void
     */
    public function boot()
    {
        if ($this->app->runningInConsole()) {
            $this->publishSeeds();
        }
    }

    public function publishSeeds()
    {
        $seedList = ['DatabaseSeeder', 'RolesTableSeeder', 'UsersTableSeeder', 'RoleEntityTableSeeder', 'EntitiesTableSeeder'];

        foreach ($seedList as $item) {
            $this->publishes([
                __DIR__ . $this->seedsPath . '/' . $item . '.php' => database_path('seeds/' . $item . '.php'),
            ], 'base-seeds');
        }
    }
}
