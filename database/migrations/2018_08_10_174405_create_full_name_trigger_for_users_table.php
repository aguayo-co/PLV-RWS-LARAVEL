<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateFullNameTriggerForUsersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('full_name')->after('last_name');
        });

        DB::unprepared('
            CREATE TRIGGER user_full_name_before_insert BEFORE INSERT ON `users`
            FOR EACH ROW
                SET NEW.full_name = TRIM(CONCAT_WS(" ", NEW.first_name, NEW.last_name))
        ');

        DB::unprepared('
            CREATE TRIGGER user_full_name_before_update BEFORE UPDATE ON `users`
            FOR EACH ROW
                SET NEW.full_name = TRIM(CONCAT_WS(" ", NEW.first_name, NEW.last_name))
        ');

        DB::statement('UPDATE users SET full_name = TRIM(CONCAT_WS(" ", users.first_name, users.last_name))');

        DB::statement('ALTER TABLE users DROP INDEX search;');
        DB::statement('ALTER TABLE users ADD FULLTEXT search(full_name)');
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        DB::statement('ALTER TABLE users DROP INDEX search;');
        DB::statement('ALTER TABLE users ADD FULLTEXT search(first_name, last_name)');

        DB::unprepared('DROP TRIGGER `user_full_name_before_insert`');
        DB::unprepared('DROP TRIGGER `user_full_name_before_update`');
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('full_name');
        });
    }
}
