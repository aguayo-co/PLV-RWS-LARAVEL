<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateChilexpressGeodataTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('chilexpress_geodata', function (Blueprint $table) {
            $table->integer('geonameid')->unsigned()->primary();
            $table->foreign('geonameid')->references('geonameid')->on('geonames');
            $table->string('name');
            $table->string('type');
            $table->string('region_cod');
            $table->string('comuna_cod')->nullable();
            $table->string('comuna_cod_ine')->nullable();
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
        Schema::dropIfExists('chilexpress_geodata');
    }
}
