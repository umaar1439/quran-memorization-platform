<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('exam_sections', function (Blueprint $table) {
            $table->id();
            $table->foreignId('exam_id')
                ->constrained('exams')
                ->cascadeOnUpdate()
                ->cascadeOnDelete();
            $table->foreignId('organization_id')
                ->constrained('organizations')
                ->cascadeOnUpdate()
                ->restrictOnDelete();
            $table->unsignedTinyInteger('surah_number');
            $table->unsignedSmallInteger('ayah_from');
            $table->unsignedSmallInteger('ayah_to');
            $table->string('section_type'); // recitation|tajweed|tafseer
            $table->unsignedInteger('max_score');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('exam_sections');
    }
};
