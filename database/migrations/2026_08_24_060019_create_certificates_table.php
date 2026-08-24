<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * id من نوع ULID (لصعوبة التخمين والتحقق العلني اللاحق).
     */
    public function up(): void
    {
        Schema::create('certificates', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignId('organization_id')
                ->constrained('organizations')
                ->cascadeOnUpdate()
                ->restrictOnDelete();
            $table->foreignId('student_id')
                ->constrained('student_profiles')
                ->cascadeOnUpdate()
                ->restrictOnDelete();
            $table->foreignId('exam_id')
                ->nullable()
                ->constrained('exams')
                ->cascadeOnUpdate()
                ->restrictOnDelete();
            $table->string('certificate_type');
            $table->timestamp('issued_at');
            $table->foreignId('issued_by')
                ->constrained('users')
                ->cascadeOnUpdate()
                ->restrictOnDelete();
            $table->string('verification_code')->unique();
            $table->string('file_path')->nullable(); // null حتى اكتمال التوليد في الـ queue job
            $table->string('status')->default('pending'); // pending|issued|failed|revoked
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('certificates');
    }
};
