<?php

namespace TrueCore\App\Providers;

use Illuminate\Support\ServiceProvider;

/**
 * Class CommandServiceProvider
 *
 * @package TrueCore\App\Providers
 */
class CommandServiceProvider extends ServiceProvider
{
    public function boot()
    {
        $this->registerCommands();
    }

    public function registerCommands()
    {
        if ($this->app->runningInConsole()) {
            $this->commands([
                \TrueCore\App\Console\Commands\Clear::class,
                \TrueCore\App\Console\Commands\SettingsSeeder::class,
                \TrueCore\App\Console\Commands\GenerateFromTable::class,
                \TrueCore\App\Console\Commands\Make\Controller\Admin::class,
                \TrueCore\App\Console\Commands\Make\Controller\Frontend::class,
                \TrueCore\App\Console\Commands\Make\Resource\Admin\ListResource::class,
                \TrueCore\App\Console\Commands\Make\Resource\Admin\FormResource::class,
                \TrueCore\App\Console\Commands\Make\Factory::class,
                \TrueCore\App\Console\Commands\Make\Observer::class,
                \TrueCore\App\Console\Commands\Make\Repository::class,
                \TrueCore\App\Console\Commands\Make\Service::class,
                \TrueCore\App\Console\Commands\Make\Structure::class,
                \TrueCore\App\Console\Commands\UploadStorageToCloud::class,
            ]);
        }
    }
}
