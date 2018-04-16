<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AlterProductSalePivotTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('product_sale', function (Blueprint $table) {
            $table->integer('sale_return_id')->unsigned()->index()->nullable();
            $table->foreign('sale_return_id')->references('id')->on('sale_returns')->onDelete('set null');
            $table->unique(['product_id', 'sale_return_id']);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('product_sale', function (Blueprint $table) {
            $table->dropUnique(['product_id', 'sale_return_id']);
            $table->dropForeign('product_sale_sale_return_id_foreign');
            $table->dropColumn('sale_return_id');
        });
    }
}
