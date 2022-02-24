<?php

use Illuminate\Database\Seeder;

class UsersTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $currentDate = date('Y-m-d H:i:s', time());

        \Illuminate\Support\Facades\DB::table('users')->updateOrInsert([
            'name' => 'admin'
        ], [
                'name'          => 'admin',
                'phone'         => '88005553535',
                'email'         => 'mail@example.com',
                'password'      => bcrypt('password'),
                'role_id'       => 1,
                'status'        => 1,
                'last_visit_at' => null,
                'created_at'    => $currentDate,
                'updated_at'    => $currentDate
        ]);
    }
}
