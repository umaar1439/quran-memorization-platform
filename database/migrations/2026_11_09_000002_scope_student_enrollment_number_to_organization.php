<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * رقم القيد كان فريداً على مستوى الجدول كله، فكان إنشاء طالب في منظمة (أ)
 * برقم قيد مستخدم في منظمة (ب) يفشل بـ 422 — قيد عابر للمستأجرين يمنع
 * الاستخدام الطبيعي ويكشف وجود بيانات في منظمة أخرى. القيد الصحيح هو
 * التفرد داخل المنظمة الواحدة.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('student_profiles', function (Blueprint $table) {
            $table->dropUnique(['enrollment_number']);
            $table->unique(['organization_id', 'enrollment_number']);
        });
    }

    public function down(): void
    {
        Schema::table('student_profiles', function (Blueprint $table) {
            $table->dropUnique(['organization_id', 'enrollment_number']);
            $table->unique(['enrollment_number']);
        });
    }
};
