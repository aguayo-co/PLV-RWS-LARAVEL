<?php

use Illuminate\Support\Facades\DB;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AlterFullTextIndexInUsersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        DB::statement('ALTER TABLE users DROP INDEX search;');
        DB::statement('ALTER TABLE users ADD FULLTEXT search(first_name, last_name)');
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        DB::statement('ALTER TABLE users DROP INDEX search;');
        DB::statement('ALTER TABLE users ADD FULLTEXT search(email, first_name, last_name)');
    }
}
