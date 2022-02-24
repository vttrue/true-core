<?php

namespace TrueCore\App\Console\Commands\Make\Controller;

use TrueCore\App\Base\GeneratorCommand;
use TrueCore\App\Http\Controllers\Admin\Base\Controller;

class Admin extends GeneratorCommand
{
    /**
     * The console command name.
     *
     * @var string
     */
    protected $name = 'make:admin_controller';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create a new admin controller';

    /**
     * The type of class being generated.
     *
     * @var string
     */
    protected $type = 'admin_controller';

    /**
     * @return array
     */
    public function getReplaces(): array
    {
        $name = $this->getFileBaseName();

        $namespace = $this->getArgumentNamespace();

        $basename = str_replace('Controller', '', $name);

        return [
            '{{ baseAdminControllerClass }}' => Controller::class,
            '{{ serviceClass }}'             => '\\App\\Services\\' . ($namespace !== '' ? $namespace . '\\' : '') . $basename . '\\' . $basename,
            '{{ serviceAlias }}'             => $basename . 'Service',
            '{{ storeRequestClass }}'        => '\\App\\Http\\Requests\\Admin\\' . ($namespace !== '' ? $namespace . '\\' : '') . 'Store' . $basename,
            '{{ storeRequestAlias }}'        => 'Store' . $basename,
            '{{ updateRequestClass }}'       => '\\App\\Http\\Requests\\Admin\\' . ($namespace !== '' ? $namespace . '\\' : '') . 'Update' . $basename,
            '{{ updateRequestAlias }}'       => 'Update' . $basename,
            '{{ listResourceClass }}'        => '\\App\\Http\\Resources\\Admin\\' . ($namespace !== '' ? $namespace . '\\' : '') . $basename . 'List',
            '{{ listResourceAlias }}'        => $basename . 'List',
            '{{ formResourceClass }}'        => '\\App\\Http\\Resources\\Admin\\' . ($namespace !== '' ? $namespace . '\\' : '') . $basename . 'Form',
            '{{ formResourceAlias }}'        => $basename . 'Form',
        ];
    }

    /**
     * Get the stub file for the generator.
     *
     * @return string
     */
    protected function getStub()
    {
        return __DIR__ . '/../../Stubs/make-controller-admin.stub';
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
        return $rootNamespace . '\\Http\\Controllers\\Admin';
    }
}
