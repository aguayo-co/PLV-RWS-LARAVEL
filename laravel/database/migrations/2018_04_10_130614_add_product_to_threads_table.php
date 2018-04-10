<?php

use Cmgmyr\Messenger\Models\Models;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddProductToThreadsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table(Models::table('threads'), function (Blueprint $table) {
            $table->integer('product_id')->unsigned();
            $table->foreign('product_id')->references('id')->on('products');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table(Models::table('threads'), function (Blueprint $table) {
            $table->dropForeign(['product_id']);
            $table->dropColumn('product_id');
        });
    }
}
