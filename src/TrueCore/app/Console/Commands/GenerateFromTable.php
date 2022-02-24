<?php

namespace TrueCore\App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Str;

class GenerateFromTable extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'generate:from_table {table} {{--namespace=}}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Generate model, service/repository/observer/factory, controller, resources, requests for given table';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $namespace = $this->option('namespace');

        $table = $this->argument('table');

        $modelName = Str::singular(ucfirst(Str::camel($table)));

        if ($namespace) {
            \Illuminate\Support\Facades\Storage::disk('app')->makeDirectory('Models/' . $namespace);
        }

        \Artisan::call('generate:modelfromtable --table=' . $table . '
         --namespace="App/Models' . ($namespace ? '/' . $namespace : '') . '"
         --folder="app/Models' . ($namespace ? '/' . $namespace : '') . '" --singular');
        \Artisan::call('make:request Admin/' . ($namespace ? $namespace . '/' : '') . 'Store' . str_replace(ucfirst($namespace),'', $modelName));
        \Artisan::call('make:request Admin/' . ($namespace ? $namespace . '/' : '') . 'Update' . str_replace(ucfirst($namespace),'', $modelName));
        \Artisan::call('make:admin_list_resource ' . ($namespace ? $namespace . '/' : '') . str_replace(ucfirst($namespace),'', $modelName) . 'List');
        \Artisan::call('make:admin_form_resource ' . ($namespace ? $namespace . '/' : '') . str_replace(ucfirst($namespace),'', $modelName) . 'Form');
        \Artisan::call('make:admin_controller ' . ($namespace ? $namespace . '/' : '') . str_replace(ucfirst($namespace),'', $modelName) . 'Controller');
        \Artisan::call('make:service_repository ' . ($namespace ? $namespace . '/' : '') .$modelName . '/' . str_replace(ucfirst($namespace),'', $modelName) . 'Repository');
        \Artisan::call('make:service_observer ' . ($namespace ? $namespace . '/' : '') . $modelName . '/'.str_replace(ucfirst($namespace),'', $modelName) . 'Observer');
        \Artisan::call('make:service_factory ' . ($namespace ? $namespace . '/' : '') . $modelName . '/'.str_replace(ucfirst($namespace),'', $modelName) . 'Factory');
        \Artisan::call('make:service_structure ' . ($namespace ? $namespace . '/' : '') . $modelName . '/'.str_replace(ucfirst($namespace),'', $modelName) . 'Structure');
        \Artisan::call('make:service ' . ($namespace ? $namespace . '/' : '') . $modelName . '/' . str_replace(ucfirst($namespace),'', $modelName));

        print "Generation success!" . PHP_EOL;
    }
}
