<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateRatingsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('ratings', function (Blueprint $table) {
            $table->integer('sale_id')->unsigned()->index();
            $table->foreign('sale_id')->references('id')->on('sales');
            $table->tinyInteger('seller_rating')->nullable();
            $table->text('seller_comment')->nullable();
            $table->tinyInteger('buyer_rating')->nullable();
            $table->text('buyer_comment')->nullable();
            $table->boolean('status');
            $table->timestamps();
            $table->primary('sale_id');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('ratings');
    }
}
