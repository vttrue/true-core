<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateEntitiesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('entities', function (Blueprint $table) {

            $table->increments('id');

            $table->string('namespace')->unique();
            $table->string('name')->unique();
            $table->string('controller')->unique();

            $table->string('policy', 255)->unique()->nullable();

            $table->unsignedInteger('sort_order')->default(0);
            $table->boolean('status')->default(0);

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
        Schema::table('entities', function (Blueprint $table) {
            $table->drop();
        });
    }
}
