<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->unsignedTinyInteger('pin_failures')->default(0);
            $table->timestamp('pin_locked_at')->nullable();
            $table->boolean('must_change_pin')->default(false);
        });
        Schema::table('exam_results', fn (Blueprint $table) => $table->string('photo_hash', 64)->nullable()->index());
    }

    public function down(): void
    {
        Schema::table('users', fn (Blueprint $table) => $table->dropColumn(['pin_failures', 'pin_locked_at', 'must_change_pin']));
        Schema::table('exam_results', fn (Blueprint $table) => $table->dropColumn('photo_hash'));
    }
};
