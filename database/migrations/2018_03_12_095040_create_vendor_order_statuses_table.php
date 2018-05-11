<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateVendorOrderStatusesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('vendor_order_statuses', function (Blueprint $table) {
            $table->increments('id');

            $table->integer('store_id');      
            $table->string('order_status_id', 60);            
            $table->string('order_status', 60);                            
            $table->string('title')->nullable();                            
            $table->string('status', 1)->default('0');
            
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
        Schema::dropIfExists('vendor_order_statuses');
    }
}
