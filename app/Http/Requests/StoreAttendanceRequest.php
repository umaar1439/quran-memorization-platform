<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\DTOs\AttendanceData;
use App\Enums\AttendanceStatus;
use App\Models\StudentAttendance;
use App\Models\StudentProfile;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * يتحقق من صحة تسجيل/تصحيح حضور طالب واحد.
 *
 * authorize() يفوّض إلى AttendancePolicy::create مع الطالب المستهدف، فتُرفض
 * محاولة تسجيل حضور طالب من حلقة أخرى بخطأ 403 قبل الوصول إلى الـ Action
 * (IDOR Defense). الاستعلام بـ withoutOrganizationScope مقصود: نحتاج رؤية
 * الطالب عبر المنظمات كي تُرجع السياسة 403 صريحاً بدل 404 مضلِّل.
 */
class StoreAttendanceRequest extends FormRequest
{
    public function authorize(): bool
    {
        $student = $this->filled('student_id')
            ? StudentProfile::withoutOrganizationScope()->find($this->integer('student_id'))
            : null;

        return $this->user()->can('create', [StudentAttendance::class, $student]);
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'student_id' => [
                'required',
                'integer',
                Rule::exists('student_profiles', 'id')->whereNull('deleted_at'),
            ],
            'status' => ['required', Rule::enum(AttendanceStatus::class)],
            'date' => ['required', 'date_format:Y-m-d', 'before_or_equal:today'],
            'notes' => ['nullable', 'string', 'max:500'],
        ];
    }

    /**
     * يحوّل البيانات المُصادَق عليها إلى DTO مكتوب الأنواع، فلا يستقبل الـ Action
     * مصفوفة حرّة (نفس نمط RecitationData/StudentData).
     */
    public function toData(): AttendanceData
    {
        return AttendanceData::fromArray($this->validated());
    }
}
