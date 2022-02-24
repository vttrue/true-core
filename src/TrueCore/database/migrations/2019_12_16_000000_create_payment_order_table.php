<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreatePaymentOrderTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('payment_order', function (Blueprint $table) {

            $table->string('code')->unique()->notNullable();
            $table->string('pp_code')->default(null);
            $table->bigInteger('amount', false, true)->default(0)->notNullable();
            $table->bigInteger('margin', false, true)->default(0)->notNullable();
            $table->string('currency', 4)->default('')->notNullable();
            $table->smallInteger('payment_processor', false, true)->default(0)->notNullable();
            $table->json('order_data')->notNullable();
            $table->json('response_data')->notNullable();
            $table->string('initiator_ip', 20)->default(null)->nullable();
            $table->string('responder_ip', 20)->default(null)->nullable();
            $table->smallInteger('status', false, true)->notNullable();

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
        Schema::table('payment_order', function(Blueprint $table) {
            $table->drop();
        });
    }
}
