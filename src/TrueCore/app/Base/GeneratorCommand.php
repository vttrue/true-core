<?php

namespace TrueCore\App\Base;

use Illuminate\Console\GeneratorCommand as BaseGeneratorCommand;
use Illuminate\Support\Str;

/**
 * Class GeneratorCommand
 *
 * @property array $replaces
 * @property string $stubName
 *
 * @package TrueCore\App\Base
 */
class GeneratorCommand extends BaseGeneratorCommand
{
    protected $replaces = [];

    protected $stubName = '';

    /**
     * @return array
     */
    public function getReplaces(): array
    {
        return [];
    }

    /**
     * @return string
     */
    public function getTrueCoreNamespace(): string
    {
        return 'TrueCore\\App\\';
    }

    /**
     * @return string
     */
    public function getFileBaseName(): string
    {
        return basename(str_replace('\\', '/', $this->argument('name')));
    }

    /**
     * @return string|string[]
     */
    public function getArgumentNamespace()
    {
        $namespace = str_replace([$this->getFileBaseName(), '/'], ['', '\\'], $this->argument('name'));

        return Str::substr($namespace, 0, Str::length($namespace) - 1);
    }

    /**
     * Get the stub file for the generator.
     *
     * @return string
     */
    protected function getStub()
    {
        return app_path() . '';
    }

    /**
     * @param string $name
     *
     * @return string|string[]
     * @throws \Illuminate\Contracts\Filesystem\FileNotFoundException
     */
    protected function buildClass($name)
    {
        $stub = parent::buildClass($name);

        $stub = str_replace(array_keys($this->getReplaces()), array_values($this->getReplaces()), $stub);

        return $stub;
    }
}
