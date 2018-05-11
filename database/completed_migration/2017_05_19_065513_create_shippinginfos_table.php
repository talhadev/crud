<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateShippinginfosTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('shippinginfo', function (Blueprint $table) {
            $table->increments('id');

            $table->integer('order_id');
            $table->string('invoice_no', 20);
            $table->string('invoice_prefix', 20);
            $table->integer('store_id');
            $table->string('store_name', 30);
            $table->string('store_url', 200);
            $table->string('currency_code', 20);
            $table->integer('currency_id');
            $table->string('currency_value', 20);
            $table->timestamp('date_added');
            $table->timestamp('date_modified');

        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('shippinginfo');
    }
}
