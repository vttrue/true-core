<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateUsersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('users', function (Blueprint $table) {

            $table->increments('id');

            $table->unsignedInteger('user_id')->index()->nullable();

            $table->unsignedInteger('role_id')->index()->nullable();

            $table->string('name');
            $table->string('phone', 32)->unique();
            $table->string('email', 64)->unique();
            $table->boolean('is_editable')->default(true);
            $table->string('password')->nullable();

            $table->rememberToken();
            $table->boolean('status')->default(0);

            $table->timestamp('last_visit_at')->nullable();
            $table->timestamps();

            $table->foreign('role_id', 'user_role_id_fk')
                ->on('roles')
                ->references('id')
                ->onUpdate('CASCADE')
                ->onDelete('SET NULL');

        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('users');
    }
}
