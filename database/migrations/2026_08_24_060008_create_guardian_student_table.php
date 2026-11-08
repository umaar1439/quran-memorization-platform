<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * جدول Pivot يربط ولي الأمر بالطالب مع صلاحيات دقيقة (JSONB) لكل ابن.
     */
    public function up(): void
    {
        Schema::create('guardian_student', function (Blueprint $table) {
            $table->id();
            $table->foreignId('guardian_id')
                ->constrained('guardians')
                ->cascadeOnUpdate()
                ->cascadeOnDelete();
            $table->foreignId('student_id')
                ->constrained('student_profiles')
                ->cascadeOnUpdate()
                ->cascadeOnDelete();
            $table->foreignId('organization_id')
                ->constrained('organizations')
                ->cascadeOnUpdate()
                ->restrictOnDelete();
            $table->json('permissions')->nullable();
            $table->timestamps();

            $table->unique(['guardian_id', 'student_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('guardian_student');
    }
};
