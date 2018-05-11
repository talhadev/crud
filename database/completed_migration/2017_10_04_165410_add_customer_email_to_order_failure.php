<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddCustomerEmailToOrderFailure extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('order_failure', function (Blueprint $table) {
            $table->string('email', 100)->nullable()->after('telephone');        
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('order_failure', function (Blueprint $table) {
            $table->dropColumn('telephone');
        });
    }
}
