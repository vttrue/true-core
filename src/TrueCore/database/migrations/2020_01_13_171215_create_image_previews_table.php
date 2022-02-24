<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

/**
 * Class CreateImagePreviewsTable
 */
class CreateImagePreviewsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('image_previews', function (Blueprint $table) {

            $table->string('entity_namespace')->index();
            $table->unsignedInteger('entity_id')->index();

            $table->string('image_path')->index();

            $table->json('preview_list')->index();

            $table->primary(['entity_namespace', 'entity_id', 'image_path'], 'image_previews_primary');

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
        Schema::table('image_previews', function (Blueprint $table) {
            $table->drop();
        });
    }
}
