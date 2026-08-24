<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * الثبات بعد الاعتماد (Immutability After Approval): RESTRICT عند الحذف،
     * والتعديل بعد status=approved يُمنع على مستوى Application Logic/Policy.
     */
    public function up(): void
    {
        Schema::create('exam_results', function (Blueprint $table) {
            $table->id();
            $table->foreignId('exam_id')
                ->constrained('exams')
                ->cascadeOnUpdate()
                ->restrictOnDelete();
            $table->foreignId('exam_section_id')
                ->constrained('exam_sections')
                ->cascadeOnUpdate()
                ->restrictOnDelete();
            $table->foreignId('student_id')
                ->constrained('student_profiles')
                ->cascadeOnUpdate()
                ->restrictOnDelete();
            $table->foreignId('organization_id')
                ->constrained('organizations')
                ->cascadeOnUpdate()
                ->restrictOnDelete();
            $table->unsignedInteger('score')->nullable();
            $table->string('grade')->nullable();
            $table->foreignId('examiner_id')
                ->constrained('users')
                ->cascadeOnUpdate()
                ->restrictOnDelete();
            $table->string('status')->default('draft'); // draft|submitted|approved
            $table->foreignId('approved_by')
                ->nullable()
                ->constrained('users')
                ->cascadeOnUpdate()
                ->restrictOnDelete();
            $table->timestamp('approved_at')->nullable();
            $table->timestamps();

            $table->index(['organization_id', 'student_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('exam_results');
    }
};
