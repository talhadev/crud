<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddAmountCityAddressEmailToShippings extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('shippings', function (Blueprint $table) {
            $table->string('amount', 30)->after('store_id')->nullable();
            $table->string('address')->after('amount')->nullable();
            $table->string('city', 30)->after('address')->nullable();
            $table->string('email', 60)->after('city')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('shippings', function (Blueprint $table) {
            $table->dropColumn('amount');
            $table->dropColumn('address');
            $table->dropColumn('city');
            $table->dropColumn('email');
        });
    }
}
