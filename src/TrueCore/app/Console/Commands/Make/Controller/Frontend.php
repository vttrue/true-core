<?php

namespace TrueCore\App\Console\Commands\Make\Controller;

use TrueCore\App\Base\GeneratorCommand;

class Frontend extends GeneratorCommand
{
    /**
     * The console command name.
     *
     * @var string
     */
    protected $name = 'make:controller_frontend';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create a new frontend controller';

    /**
     * The type of class being generated.
     *
     * @var string
     */
    protected $type = 'frontend_controller';

    /**
     * @return array
     */
    public function getReplaces(): array
    {
        $model = str_replace('Controller', '', $this->argument('name'));

        return [
            '{{ modelClass }}' => '\\App\\Models\\' . $model,
            '{{ modelAlias }}' => $model . 'Model',
        ];
    }

    /**
     * Get the stub file for the generator.
     *
     * @return string
     */
    protected function getStub()
    {
        return __DIR__ . '/../../Stubs/make-controller-frontend.stub';
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
        $name = str_replace('Controller', '', $this->argument('name'));

        return $rootNamespace . '\\Services\\' . $name;
    }
}
