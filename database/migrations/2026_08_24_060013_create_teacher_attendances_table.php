<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('teacher_attendances', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')
                ->constrained('organizations')
                ->cascadeOnUpdate()
                ->restrictOnDelete();
            $table->foreignId('teacher_id')
                ->constrained('users')
                ->cascadeOnUpdate()
                ->restrictOnDelete();
            $table->foreignId('mosque_id')
                ->constrained('mosques')
                ->cascadeOnUpdate()
                ->restrictOnDelete();
            $table->date('date');
            $table->timestamp('first_activity_at')->nullable();
            $table->timestamp('last_activity_at')->nullable();
            $table->unsignedInteger('actions_count')->default(0);
            $table->timestamps();

            $table->unique(['organization_id', 'teacher_id', 'mosque_id', 'date'], 'teacher_attendance_unique_per_mosque');
            $table->index(['organization_id', 'teacher_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('teacher_attendances');
    }
};
