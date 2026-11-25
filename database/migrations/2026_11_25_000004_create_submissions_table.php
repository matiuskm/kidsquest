<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('submissions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('task_id')->constrained();
            $table->foreignId('child_id')->constrained('users');
            $table->date('period_date');
            $table->string('photo_path')->nullable();
            $table->string('status', 16)->default('pending')->index();
            $table->string('reject_note')->nullable();
            $table->timestamp('reviewed_at')->nullable();
            $table->timestamps();

            $table->index(['task_id', 'child_id', 'period_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('submissions');
    }
};
