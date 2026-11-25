<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /** Huruf boleh berplus/berminus (A+, B-). */
    public function up(): void
    {
        Schema::table('exam_results', function (Blueprint $table) {
            $table->string('claimed_letter', 2)->nullable()->change();
            $table->string('final_letter', 2)->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('exam_results', function (Blueprint $table) {
            $table->string('claimed_letter', 1)->nullable()->change();
            $table->string('final_letter', 1)->nullable()->change();
        });
    }
};
