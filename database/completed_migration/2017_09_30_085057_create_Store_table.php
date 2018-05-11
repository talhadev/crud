<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateStoreTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('stores', function (Blueprint $table) {
            $table->increments('id');

            $table->integer('technify_store_id');
            $table->integer('user_id');
            $table->string('name', 50);           
            $table->string('store_url');
            $table->string('telephone', 30);
            $table->string('password', 50);
            $table->string('address', 150);           
            $table->string('email', 50)->unique();          
            $table->string('support_email');                                  
            $table->string('uuid', 30);                

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
        Schema::dropIfExists('stores');
    }
}
