<?php

namespace TrueCore\App\Console\Commands\Make\Resource\Admin;

use TrueCore\App\Base\GeneratorCommand;

class ListResource extends GeneratorCommand
{
    /**
     * The console command name.
     *
     * @var string
     */
    protected $name = 'make:admin_list_resource';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create a new admin list resource';

    /**
     * The type of class being generated.
     *
     * @var string
     */
    protected $type = 'admin_list_resource';

    /**
     * @return array
     */
    public function getReplaces(): array
    {
        $basename = str_replace('List', '', $this->getFileBaseName());

        $namespace = $this->getArgumentNamespace();

        return [
            '{{ structureClass }}' => '\\App\\Services\\' . ($namespace !== '' ? $namespace . '\\' : '') . $basename . '\\' . $basename . 'Structure',
        ];
    }

    /**
     * Get the stub file for the generator.
     *
     * @return string
     */
    protected function getStub()
    {
        return __DIR__ . '/../../../Stubs/make-list-resource-admin.stub';
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

        return $rootNamespace . '\\Http\\Resources\\Admin';
    }
}
