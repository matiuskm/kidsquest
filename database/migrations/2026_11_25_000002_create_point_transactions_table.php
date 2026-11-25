<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('point_transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->integer('amount');
            $table->string('type', 16);
            $table->nullableMorphs('ref');
            $table->string('note')->nullable();
            // Hanya diisi untuk bonus ulang tahun; NULL berulang diizinkan MySQL, jadi tipe lain tidak bentrok.
            $table->unsignedSmallInteger('year')->nullable();
            $table->timestamps();

            $table->unique(['user_id', 'type', 'year']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('point_transactions');
    }
};
