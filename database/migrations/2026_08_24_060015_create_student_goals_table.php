<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('student_goals', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')
                ->constrained('organizations')
                ->cascadeOnUpdate()
                ->restrictOnDelete();
            $table->foreignId('student_id')
                ->constrained('student_profiles')
                ->cascadeOnUpdate()
                ->restrictOnDelete();
            $table->string('period_type'); // daily|weekly|monthly
            $table->string('target_type'); // memorization|revision
            $table->foreignId('surah_id')
                ->nullable()
                ->constrained('surahs')
                ->cascadeOnUpdate()
                ->nullOnDelete();
            $table->unsignedTinyInteger('juz_number')->nullable();
            $table->unsignedInteger('target_amount');
            $table->unsignedInteger('achieved_amount')->default(0);
            $table->unsignedTinyInteger('completion_percentage')->default(0);
            $table->date('period_start');
            $table->date('period_end');
            $table->string('status')->default('active'); // active|completed|failed|cancelled
            $table->timestamps();

            $table->index(['organization_id', 'student_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('student_goals');
    }
};
