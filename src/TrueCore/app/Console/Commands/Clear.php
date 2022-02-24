<?php

namespace TrueCore\App\Console\Commands;

use Illuminate\Console\Command;

class Clear extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'clear:start {--domain=}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Clear all cache';

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
        $domain = (in_array($this->option('domain'), ['', null], true)) ? null : $this->option('domain');

        $options = (($domain !== null) ? ['--domain' => $domain] : []);

        $this->call('cache:clear', $options);
        $this->call('config:clear', $options);
        $this->call('view:clear', $options);
        $this->call('route:clear', $options);
        $this->call('debugbar:clear', $options);
        $this->call('optimize:clear', $options);
        $this->call('optimize', $options);

        print "Cache was cleared." . PHP_EOL;
    }
}
