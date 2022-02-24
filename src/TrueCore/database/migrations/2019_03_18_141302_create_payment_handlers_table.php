<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreatePaymentHandlersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('payment_handlers', function (Blueprint $table) {

            $table->increments('id');

            $table->string('name', 255);

            $table->string('handler', 255)->comment('PaymentHandler class namespace');

            $table->json('params')->comment('PaymentHandler class parameters');

            $table->unsignedInteger('sort_order')->default(0);

            $table->boolean('status')->default(0)->index();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('payment_handlers', function (Blueprint $table) {
            $table->drop();
        });
    }
}
