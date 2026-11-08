<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * فهارس مركبة لجدول exams مطابقة لاستعلامات قائمة الامتحانات الفعلية
 * (ExamDirectoryQuery): فلترة اختيارية بالحلقة أو المسجد + الترتيب على
 * scheduled_at مع تقييد المستأجر organization_id.
 * الفهارس الحالية تغطي المفاتيح الأجنبية فقط، وهي غير كافية عند الترتيب/الفلترة.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('exams', function (Blueprint $table) {
            $table->index(['organization_id', 'scheduled_at'], 'exams_organization_scheduled_index');
            $table->index(['circle_id', 'scheduled_at'], 'exams_circle_scheduled_index');
            $table->index(['mosque_id', 'scheduled_at'], 'exams_mosque_scheduled_index');
        });
    }

    public function down(): void
    {
        Schema::table('exams', function (Blueprint $table) {
            $table->dropIndex('exams_organization_scheduled_index');
            $table->dropIndex('exams_circle_scheduled_index');
            $table->dropIndex('exams_mosque_scheduled_index');
        });
    }
};
