<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateCustomFieldsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('custom_fields', function (Blueprint $table) {

            $table->increments('id');

            $table->unsignedInteger('user_id')->index()->nullable();

            $table->string('code')->unique()->index();
            $table->integer('sort_order')->default(0);
            $table->boolean('status')->default(true)->index();

            $table->foreign('user_id', 'custom_fields_user_id_fk')
                ->on('users')
                ->references('id')
                ->onUpdate('CASCADE')
                ->onDelete('SET NULL');

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('custom_fields');
    }
}
