<?php

use Illuminate\Database\Seeder;

class RolesTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $currentDate = date('Y-m-d H:i:s', time());

        \Illuminate\Support\Facades\DB::table('roles')->updateOrInsert([
            'name' => 'Администратор'
        ], [

                'name'          => 'Администратор',
                'created_at'    => $currentDate,
                'updated_at'    => $currentDate
        ]);
    }
}
