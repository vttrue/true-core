<?php

namespace TrueCore\App\Console\Commands\Make;

use TrueCore\App\Base\GeneratorCommand;

class Structure extends GeneratorCommand
{
    /**
     * The console command name.
     *
     * @var string
     */
    protected $name = 'make:service_structure';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create a new structure';

    /**
     * The type of class being generated.
     *
     * @var string
     */
    protected $type = 'structure';

    /**
     * @return array
     */
    public function getReplaces(): array
    {
        $basename = str_replace('Structure', '', $this->getFileBaseName());

        return [
            '{{ repositoryClass }}' => $basename . 'Repository',
        ];
    }

    /**
     * Get the stub file for the generator.
     *
     * @return string
     */
    protected function getStub()
    {
        return __DIR__ . '/../Stubs/make-structure.stub';
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
