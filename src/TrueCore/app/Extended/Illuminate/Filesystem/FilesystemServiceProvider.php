<?php

namespace TrueCore\App\Extended\Illuminate\Filesystem;

use Illuminate\Filesystem\FilesystemServiceProvider as BaseFilesystemServiceProvider;

/**
 * Class FilesystemServiceProvider
 *
 * @package TrueCore\App\Extended\Illuminate\Filesystem
 */
class FilesystemServiceProvider extends BaseFilesystemServiceProvider
{
    /**
     * Register the filesystem manager.
     *
     * @return void
     */
    protected function registerManager()
    {
        $this->app->singleton('filesystem', function ($app) {
            return new FilesystemManager($app);
        });
    }
}
