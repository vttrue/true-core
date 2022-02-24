<?php

namespace TrueCore\App\Console\Commands\Make;

use TrueCore\App\Base\GeneratorCommand;

class Observer extends GeneratorCommand
{
    /**
     * The console command name.
     *
     * @var string
     */
    protected $name = 'make:service_observer';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create a new observer';

    /**
     * The type of class being generated.
     *
     * @var string
     */
    protected $type = 'observer';

    /**
     * @return array
     */
    public function getReplaces(): array
    {
        return [];
    }

    /**
     * Get the stub file for the generator.
     *
     * @return string
     */
    protected function getStub()
    {
        return __DIR__ . '/../Stubs/make-observer.stub';
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
        $name = str_replace('Observer', '', $this->argument('name'));

        return $rootNamespace . '\\Services';
    }
}
