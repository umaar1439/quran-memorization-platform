<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\DTOs\BulkAttendanceData;
use App\Enums\AttendanceStatus;
use App\Models\StudentAttendance;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * يتحقق من صحة حفظ ورقة الحضور اليومية كاملة (حلقة + تاريخ + سجلات الطلاب).
 *
 * الحمولة محدودة العدد (tahfeez.bulk_max_records) لمنع طلب واحد يحمل آلاف
 * السجلات؛ و`distinct` يمنع تكرار الطالب نفسه داخل الورقة قبل أي كتابة.
 * التحقق من انتماء كل طالب للحلقة المطلوبة يتم في الـ Action لأنه يحتاج
 * تحميل الحلقات دفعة واحدة (لا استعلام لكل سجل).
 */
class BulkAttendanceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('create', StudentAttendance::class);
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'circle_id' => [
                'required',
                'integer',
                Rule::exists('circles', 'id')->whereNull('deleted_at'),
            ],
            'date' => ['required', 'date_format:Y-m-d', 'before_or_equal:today'],
            'records' => ['required', 'array', 'min:1', 'max:'.(int) config('tahfeez.bulk_max_records')],
            'records.*.student_id' => [
                'required',
                'integer',
                'distinct',
                Rule::exists('student_profiles', 'id')->whereNull('deleted_at'),
            ],
            'records.*.status' => ['required', Rule::enum(AttendanceStatus::class)],
            'records.*.notes' => ['nullable', 'string', 'max:500'],
        ];
    }

    public function toData(): BulkAttendanceData
    {
        return BulkAttendanceData::fromArray($this->validated());
    }
}
