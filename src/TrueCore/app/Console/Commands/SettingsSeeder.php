<?php

namespace TrueCore\App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class SettingsSeeder extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'settings:seed {--forceUpdate=}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Settings seeder';

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
     * Execute the console command. forceUpdate option example: --forceUpdate=contacts,shop,seo
     *
     * @return void
     *
     * @throws \Exception
     */
    public function handle()
    {
        if ( is_string($this->option('forceUpdate')) && $this->option('forceUpdate') !== '' ) {

            $toForceUpdateGroupList = explode(',', $this->option('forceUpdate'));

            foreach ($toForceUpdateGroupList as $group) {

                $handlerReflector = new \ReflectionClass('App\Console\Commands\SettingHandlers\\' . ucfirst($group) . 'Handler');

                if ( $handlerReflector->isInstantiable() === false ) {
                    throw new \Exception('Cannot instantiate ' . $group . ' handler. Need to create SettingHandlers\\' . ucfirst($group) . 'Handler.');
                }

                $handlerInstance = $handlerReflector->newInstanceWithoutConstructor();
                $handlerInstance::handle();

                dump(ucfirst($group) . ' group settings has been force-updated.');
            }
        }

        $settingList = null;

        if ( file_exists(app_path('Console/Commands/SettingHandlers/settings.json')) ) {

            try {
                $settingList = json_decode(file_get_contents(app_path('Console/Commands/SettingHandlers/settings.json')), true);
            } catch (\Throwable $e) {
            }
        }

        if ( $settingList === null ) {
            $json = [
                [
                    "group" => "contacts",
                    "key"   => "phones",
                    "value" => null,
                    "json"  => 1,
                ],
                [
                    "group" => "contacts",
                    "key"   => "companyName",
                    "value" => "Default",
                    "json"  => 0,
                ],
                [
                    "group" => "contacts",
                    "key"   => "schedule",
                    "value" => "Ежедневно с 10:00 до 22:00",
                    "json"  => 0,
                ],
                [
                    "group" => "contacts",
                    "key"   => "email",
                    "value" => "default@true-cms.ru",
                    "json"  => 0,
                ],
                [
                    "group" => "contacts",
                    "key"   => "workingHours",
                    "value" => null,
                    "json"  => 1,
                ],
                [
                    "group" => "contacts",
                    "key"   => "addresses",
                    "value" => "[{\"address\":\"Default\",\"coordinates\":[]}]",
                    "json"  => 1,
                ],
            ];
        }

        $currentDate = date('Y-m-d H:i:s', time());

        foreach ($settingList as $item) {

            $existing = DB::table('settings')->where('group', '=', $item['group'])->where('key', '=', $item['key'])->first();

            if ( !$existing ) {

                DB::table('settings')->insert([
                                                  'group'      => $item['group'],
                                                  'key'        => $item['key'],
                                                  'value'      => $item['value'],
                                                  'json'       => $item['json'],
                                                  'created_at' => $currentDate,
                                                  'updated_at' => $currentDate,
                                              ]);

                dump('Setting with group: ' . $item['group'] . ' and key: ' . $item['key'] . ' has been saved with value: ' . $item['value']);
            }
        }

        DB::table('settings')->whereNotIn('group', array_values(array_unique(array_column($settingList, 'group'))))->delete();
    }
}
