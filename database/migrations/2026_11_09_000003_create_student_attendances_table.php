<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * حضور الطلاب اليومي.
     *
     * قيد فريد (organization_id, student_id, date) هو أساس دلالات التصحيح:
     * تسجيل الحضور لنفس الطالب في نفس اليوم يُحدِّث السجل القائم (Upsert) ولا
     * يُنشئ نسخة ثانية — يمنع ازدواج الحضور وتضخيم نسب الحضور.
     *
     * circle_id/mosque_id يُحفظان لحظة التسجيل (Historical Binding) كي يبقى
     * السجل صحيحاً بعد نقل الطالب إلى حلقة أخرى، كما تنص وثيقة التحقق المعماري.
     *
     * restrictOnDelete على student_id/circle_id/mosque_id: سجل الحضور تاريخ
     * لا يُحذف مع حذف كيانه المرتبط (وهذه الكيانات تستخدم Soft Deletes أصلاً).
     * recorded_by يقبل nullOnDelete كي يبقى السجل بعد حذف حساب من سجّله.
     */
    public function up(): void
    {
        Schema::create('student_attendances', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')
                ->constrained('organizations')
                ->cascadeOnUpdate()
                ->restrictOnDelete();
            $table->foreignId('student_id')
                ->constrained('student_profiles')
                ->cascadeOnUpdate()
                ->restrictOnDelete();
            $table->foreignId('circle_id')
                ->nullable()
                ->constrained('circles')
                ->cascadeOnUpdate()
                ->restrictOnDelete();
            $table->foreignId('mosque_id')
                ->nullable()
                ->constrained('mosques')
                ->cascadeOnUpdate()
                ->restrictOnDelete();
            $table->date('date');
            $table->string('status'); // present|absent|late|excused
            $table->text('notes')->nullable();
            $table->foreignId('recorded_by')
                ->nullable()
                ->constrained('users')
                ->cascadeOnUpdate()
                ->nullOnDelete();
            $table->timestamps();

            $table->unique(['organization_id', 'student_id', 'date']);
            $table->index(['organization_id', 'date']);
            $table->index(['organization_id', 'circle_id', 'date']);
            $table->index(['organization_id', 'mosque_id', 'date']);
            $table->index(['organization_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('student_attendances');
    }
};
