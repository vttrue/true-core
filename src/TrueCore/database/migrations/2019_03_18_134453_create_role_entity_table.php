<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateRoleEntityTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('role_entity', function (Blueprint $table) {

            $table->unsignedInteger('role_id');
            $table->unsignedInteger('entity_id');

            $table->json('permissions');

            $table->primary(['role_id', 'entity_id']);
            $table->index(['role_id', 'entity_id']);

            $table->foreign('role_id', 'role_entity_role_id_fk')
                ->on('roles')
                ->references('id')
                ->onUpdate('CASCADE')
                ->onDelete('CASCADE');

            $table->foreign('entity_id', 'role_entity_entity_id_fk')
                ->on('entities')
                ->references('id')
                ->onUpdate('CASCADE')
                ->onDelete('CASCADE');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('role_entity', function (Blueprint $table) {
            $table->dropForeign('role_entity_role_id_fk');
            $table->dropForeign('role_entity_entity_id_fk');
            $table->drop();
        });
    }
}
