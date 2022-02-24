<?php

use Illuminate\Database\Seeder;

class RoleEntityTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $entities = \Illuminate\Support\Facades\DB::table('entities')->select('*')->get();

        foreach ($entities AS $entity) {

            $role = \TrueCore\App\Services\System\Role::getRandom();

            \Illuminate\Support\Facades\DB::table('role_entity')->updateOrInsert(['role_id' => $role[0]->mapDetail(['id'])['id'], 'entity_id'   => $entity->id], [
                'permissions' => '["read","write"]',
            ]);
        }
    }
}
