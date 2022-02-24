<?php

namespace TrueCore\App\Console\Commands\Make;

use Illuminate\Support\Str;
use TrueCore\App\Base\GeneratorCommand;

class Repository extends GeneratorCommand
{
    /**
     * The console command name.
     *
     * @var string
     */
    protected $name = 'make:service_repository';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create a new repository';

    /**
     * The type of class being generated.
     *
     * @var string
     */
    protected $type = 'repository';

    /**
     * @return string|string[]
     */
    public function getArgumentNamespace()
    {
        $namespace = str_replace([$this->getEntityName().'/'.$this->getFileBaseName(), '/'], ['', '\\'], $this->argument('name'));

        return Str::substr($namespace, 0, Str::length($namespace) - 1);
    }

    /**
     * @return string
     */
    public function getEntityName(): string
    {
        return str_replace('Repository', '', $this->getFileBaseName());
    }

    /**
     * @return array
     */
    public function getReplaces(): array
    {
        $entityName = $this->getEntityName();
        $namespace = $this->getArgumentNamespace();

        return [
            '{{ modelClass }}' => '\\App\\Models\\' . ($namespace !== '' ? $namespace . '\\' : '') . $entityName,
            '{{ modelAlias }}' => $entityName . 'Model',
        ];
    }

    /**
     * Get the stub file for the generator.
     *
     * @return string
     */
    protected function getStub()
    {
        return __DIR__ . '/../Stubs/make-repository.stub';
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
        return $rootNamespace . '\\Services';
    }
}
