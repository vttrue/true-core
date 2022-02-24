<?php

use Illuminate\Database\Seeder;

class EntitiesTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $currentDate = date('Y-m-d H:i:s', time());

        $entityList = [
            [
                'name'       => 'Система: Настройки',
                'namespace'  => \TrueCore\App\Services\System\Setting::class,
                'controller' => \TrueCore\App\Http\Controllers\Admin\System\SettingController::class,
                'policy'     => \TrueCore\App\Policies\System\SettingsPolicy::class,
                'sort_order' => 0,
                'status'     => 1,
                'created_at' => $currentDate,
                'updated_at' => $currentDate,
            ],
            [
                'name'       => 'Система: Пользователи',
                'namespace'  => \TrueCore\App\Services\System\User::class,
                'controller' => \TrueCore\App\Http\Controllers\Admin\System\UserController::class,
                'policy'     => \TrueCore\App\Policies\System\UserPolicy::class,
                'sort_order' => 0,
                'status'     => 1,
                'created_at' => $currentDate,
                'updated_at' => $currentDate,
            ],
            [
                'name'       => 'Система: Роли',
                'namespace'  => \TrueCore\App\Services\System\Role::class,
                'controller' => \TrueCore\App\Http\Controllers\Admin\System\RoleController::class,
                'policy'     => \TrueCore\App\Policies\System\RolePolicy::class,
                'sort_order' => 0,
                'status'     => 1,
                'created_at' => $currentDate,
                'updated_at' => $currentDate,
            ],
        ];

        foreach ($entityList as $entity) {

            \Illuminate\Support\Facades\DB::table('entities')->updateOrInsert([
                'namespace' => $entity['namespace']],
                $entity
            );

        }
    }
}
