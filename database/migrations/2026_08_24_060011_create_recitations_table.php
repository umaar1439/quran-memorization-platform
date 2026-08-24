<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * سجل التسميع الأكاديمي — لا CASCADE DELETE على student_id/teacher_id (RESTRICT)
     * لأن السجل التاريخي للتسميع لا يُحذف حتى مع حذف الطالب/المعلم.
     */
    public function up(): void
    {
        Schema::create('recitations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')
                ->constrained('organizations')
                ->cascadeOnUpdate()
                ->restrictOnDelete();
            $table->foreignId('student_id')
                ->constrained('student_profiles')
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
            $table->foreignId('circle_id')
                ->constrained('circles')
                ->cascadeOnUpdate()
                ->restrictOnDelete();
            $table->string('type'); // new|near_revision|far_revision
            $table->unsignedTinyInteger('surah_number');
            $table->unsignedSmallInteger('ayah_from');
            $table->unsignedSmallInteger('ayah_to');
            $table->string('rating')->nullable(); // excellent|very_good|good|needs_work
            $table->text('notes')->nullable();
            $table->timestamp('performed_at');
            $table->uuid('idempotency_key');
            $table->timestamps();

            $table->unique(['organization_id', 'idempotency_key']);
            $table->index(['organization_id', 'mosque_id']);
            $table->index(['organization_id', 'circle_id']);
            $table->index(['organization_id', 'student_id']);
            $table->index(['organization_id', 'teacher_id']);
            $table->index(['organization_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('recitations');
    }
};
