<?php

namespace TrueCore\App\Console\Commands\Make;

use TrueCore\App\Base\GeneratorCommand;

class Service extends GeneratorCommand
{
    /**
     * The console command name.
     *
     * @var string
     */
    protected $name = 'make:service';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create a new service';

    /**
     * The type of class being generated.
     *
     * @var string
     */
    protected $type = 'service';

    public function getReplaces(): array
    {
        $basename = $this->getFileBaseName();

        return [
            '{{ repositoryClass }}' => $basename . 'Repository',
            '{{ observerClass }}'   => $basename . 'Observer',
            '{{ factoryClass }}'    => $basename . 'Factory',
            '{{ structureClass }}'  => $basename . 'Structure',
        ];
    }

    /**
     * Get the stub file for the generator.
     *
     * @return string
     */
    protected function getStub()
    {
        return __DIR__ . '/../Stubs/make-service.stub';
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
