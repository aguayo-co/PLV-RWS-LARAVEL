<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AlterUniqueForEmailInUsersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropUnique(['email']);
            $table->boolean('active')->after('vacation_mode')->nullable();
            $table->unique(['email', 'active']);
        });

        // UNIQUE constrains ignore NULL values in InnoDB.
        // We need to make sure we have only 1 non-deleted account
        // per email (a UNIQUE constrain for active emails).
        // We enforce it at the DB level with an `active` column and a trigger.
        // Basically rows with NULL on `deleted_at` are `active`.
        // We set NULL on `active` when `deleted_at` is NOT NULL, this way we will only
        // get an error when two rows with the same email have NULL on deleted_at,
        // given that both will have `1` on `active`.
        DB::statement('UPDATE users SET active = IF(deleted_at IS NULL, 1, NULL)');

        DB::unprepared('
            CREATE TRIGGER user_active_before_insert BEFORE INSERT ON `users`
            FOR EACH ROW
                SET NEW.active = IF(NEW.deleted_at IS NULL, 1, NULL)
        ');

        DB::unprepared('
            CREATE TRIGGER user_active_before_update BEFORE UPDATE ON `users`
            FOR EACH ROW
                SET NEW.active = IF(NEW.deleted_at IS NULL, 1, NULL)
        ');
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('users', function (Blueprint $table) {
            DB::unprepared('DROP TRIGGER `user_active_before_insert`');
            DB::unprepared('DROP TRIGGER `user_active_before_update`');

            $table->dropUnique(['email', 'active']);
            $table->dropColumn('active');
            $table->unique(['email']);
        });
    }
}
