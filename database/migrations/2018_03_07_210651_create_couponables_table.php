<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateCouponablesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('couponables', function (Blueprint $table) {
            $table->integer('coupon_id')->unsigned()->index();
            $table->integer('couponable_id')->unsigned();
            $table->string('couponable_type');
            $table->index(['couponable_id', 'couponable_type']);
            $table->unique(['coupon_id', 'couponable_id', 'couponable_type']);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('couponables');
    }
}
