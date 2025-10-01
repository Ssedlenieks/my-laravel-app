<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddProfilePhotoToUsersTable extends Migration
{
    public function up()
    {
        if (!Schema::hasColumn('users', 'profile_photo')) {
            Schema::table('users', function (Blueprint $table) {
                if (Schema::hasColumn('users', 'bio')) {
                    $table->string('profile_photo')->nullable()->after('bio');
                } else {
                    $table->string('profile_photo')->nullable();
                }
            });
        }
    }

    public function down()
    {
        if (Schema::hasColumn('users', 'profile_photo')) {
            Schema::table('users', function (Blueprint $table) {
                $table->dropColumn('profile_photo');
            });
        }
    }
}
