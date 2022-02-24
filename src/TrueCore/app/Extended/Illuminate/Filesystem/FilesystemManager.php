<?php

namespace TrueCore\App\Extended\Illuminate\Filesystem;

use Illuminate\Filesystem\FilesystemManager as BaseFilesystemManager;
use Illuminate\Support\Arr;
use League\Flysystem\AdapterInterface;
use League\Flysystem\Filesystem as Flysystem;
use League\Flysystem\FilesystemInterface;
use League\Flysystem\Cached\CachedAdapter;

/**
 * Class FilesystemManager
 *
 * @package TrueCore\App\Extended\Illuminate\Filesystem
 */
class FilesystemManager extends BaseFilesystemManager
{
    /**
     * Adapt the filesystem implementation.
     *
     * @param  \League\Flysystem\FilesystemInterface  $filesystem
     * @return \Illuminate\Contracts\Filesystem\Filesystem
     */
    protected function adapt(FilesystemInterface $filesystem)
    {
        return new FilesystemAdapter($filesystem);
    }

    /**
     * Create a Flysystem instance with the given adapter.
     *
     * @param  \League\Flysystem\AdapterInterface  $adapter
     * @param  array  $config
     * @return \League\Flysystem\FilesystemInterface
     */
    protected function createFlysystem(AdapterInterface $adapter, array $config)
    {
        $cache = Arr::pull($config, 'cache');

        $config = Arr::only($config, ['visibility', 'disable_asserts', 'url', 'urlPrefixRewrite']);

        if ($cache) {
            $adapter = new CachedAdapter($adapter, $this->createCacheStore($cache));
        }

        return new Flysystem($adapter, count($config) > 0 ? $config : null);
    }
}
