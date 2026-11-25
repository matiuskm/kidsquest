<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /** Pelajaran bahasa Inggris dinilai dengan huruf, bukan angka. */
    public function up(): void
    {
        Schema::table('exam_results', function (Blueprint $table) {
            $table->unsignedTinyInteger('claimed_score')->nullable()->change();
            $table->string('claimed_letter', 1)->nullable()->after('claimed_score');
            $table->string('final_letter', 1)->nullable()->after('final_score');
        });
    }

    public function down(): void
    {
        Schema::table('exam_results', function (Blueprint $table) {
            $table->dropColumn(['claimed_letter', 'final_letter']);
        });
    }
};
