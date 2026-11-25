<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('exam_results', function (Blueprint $table) {
            $table->id();
            $table->foreignId('child_id')->constrained('users');
            $table->string('subject', 60);
            $table->unsignedTinyInteger('claimed_score');
            $table->unsignedTinyInteger('final_score')->nullable();
            $table->string('photo_path')->nullable(); // dikosongkan saat akun anak dihapus
            $table->string('status', 16)->default('pending')->index();
            $table->string('reject_note')->nullable();
            $table->unsignedTinyInteger('points_awarded')->nullable();
            $table->timestamp('reviewed_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('exam_results');
    }
};
