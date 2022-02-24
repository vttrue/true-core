<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateCustomFieldRelatedEntityTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('custom_field_related_entity', function (Blueprint $table) {

            $table->increments('id');

            $table->unsignedInteger('related_entity_id')->index();
            $table->string('related_entity_type')->index();

            $table->index(['related_entity_id','related_entity_type'], 'related_entity_id_type_index');

            $table->unsignedInteger('custom_field_id')->index();
            $table->unsignedInteger('custom_field_type_id')->index();

            $table->json('validation_rules')->nullable();
            $table->json('settings')->nullable();

            $table->unsignedInteger('sort_order')->default(0);
            $table->boolean('status')->default(true)->index();

            $table->timestamps();

            $table->foreign('custom_field_id', 'cfe_p_custom_field_id_fk')
                ->on('custom_fields')
                ->references('id')
                ->onUpdate('CASCADE')
                ->onDelete('CASCADE');

            $table->foreign('custom_field_type_id', 'cfe_p_custom_field_type_id_fk')
                ->on('custom_field_types')
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
        Schema::dropIfExists('custom_field_related_entity');
    }
}
