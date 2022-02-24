<?php

namespace TrueCore\App\Console\Commands\Make;

use Illuminate\Support\Str;
use TrueCore\App\Base\GeneratorCommand;

class Factory extends GeneratorCommand
{
    /**
     * The console command name.
     *
     * @var string
     */
    protected $name = 'make:service_factory';
    protected $stubName = 'make-factory';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create a new factory';

    /**
     * The type of class being generated.
     *
     * @var string
     */
    protected $type = 'factory';

    /**
     * @return string|string[]
     */
    public function getArgumentNamespace()
    {
        $namespace = str_replace([$this->getEntityName() . '/' . $this->getFileBaseName(), '/'], ['', '\\'], $this->argument('name'));

        return Str::substr($namespace, 0, Str::length($namespace) - 1);
    }

    /**
     * @return string
     */
    public function getEntityName(): string
    {
        return str_replace('Factory', '', $this->getFileBaseName());
    }

    /**
     * @return array
     */
    public function getReplaces(): array
    {
        $entityName = $this->getEntityName();
        $namespace = $this->getArgumentNamespace();

        return [
            '{{ modelClass }}'      => '\\App\\Models\\' . ($namespace !== '' ? $namespace . '\\' : '') . $entityName,
            '{{ modelAlias }}'      => $entityName . 'Model',
            '{{ serviceClass }}'    => $entityName,
            '{{ repositoryClass }}' => $entityName . 'Repository',
            '{{ observerClass }}'   => $entityName . 'Observer',
        ];
    }

    protected function getStub()
    {
        return __DIR__ . '/../Stubs/make-factory.stub';
    }

    /**
     * Get the default namespace for the class.
     *
     * @param string $rootNamespace
     *
     * @return string
     */
    protected function getDefaultNamespace($rootNamespace)
    {
//        $name = str_replace('Factory', '', $this->argument('name'));

        return $rootNamespace . '\\Services';
    }
}
