<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('rewards', function (Blueprint $table) {
            $table->id();
            $table->string('name', 100);
            $table->string('icon', 16)->nullable();
            $table->unsignedInteger('cost');
            $table->boolean('active')->default(true);
            $table->softDeletes();
            $table->timestamps();
        });

        Schema::create('redemptions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('reward_id')->constrained();
            $table->foreignId('child_id')->constrained('users');
            $table->unsignedInteger('cost'); // snapshot saat klaim
            $table->string('status', 16)->default('pending')->index();
            $table->string('reject_note')->nullable();
            $table->timestamp('reviewed_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('redemptions');
        Schema::dropIfExists('rewards');
    }
};
