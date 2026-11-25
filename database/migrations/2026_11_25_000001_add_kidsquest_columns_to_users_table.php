<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('email')->nullable()->change();
            $table->string('password')->nullable()->change();
            $table->string('role', 16)->default('parent')->after('name')->index();
            $table->string('avatar', 16)->nullable()->after('role');
            $table->string('pin_hash')->nullable()->after('avatar');
            $table->unsignedTinyInteger('birthday_month')->nullable()->after('pin_hash');
            $table->unsignedTinyInteger('birthday_day')->nullable()->after('birthday_month');
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropSoftDeletes();
            $table->dropColumn(['role', 'avatar', 'pin_hash', 'birthday_month', 'birthday_day']);
        });
    }
};
