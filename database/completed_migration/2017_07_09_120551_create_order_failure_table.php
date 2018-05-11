<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateOrderFailureTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('order_failure', function (Blueprint $table) {
            $table->increments('id');

            $table->string('order_id', 20);            
            $table->string('store_id', 20);            
            $table->string('failure_address');            
            $table->string('failure_city', 30);            
            $table->text('orderinfo');            

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
        Schema::dropIfExists('order_failure');
    }
}
