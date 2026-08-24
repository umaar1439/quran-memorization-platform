<?php

namespace App\Http\Requests;

use App\Enums\GoalStatus;
use App\Enums\GoalTargetType;
use App\Enums\PeriodType;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * يتحقق من صحة بيانات تحديث هدف طالب. authorize() يفوّض إلى
 * StudentGoalPolicy::update التي تمنع التعديل بعد الاكتمال (§5).
 * status يقبل فقط active/completed عبر هذا المسار (لا failed/cancelled
 * — تلك تُدار عبر مسارات إدارية أخرى إن لزم لاحقاً).
 */
class UpdateStudentGoalRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('update', $this->route('student_goal'));
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'period_type' => ['sometimes', Rule::enum(PeriodType::class)],
            'target_type' => ['sometimes', Rule::enum(GoalTargetType::class)],
            'surah_id' => ['nullable', 'integer', Rule::exists('surahs', 'id')],
            'juz_number' => ['nullable', 'integer', 'min:1', 'max:30'],
            'target_amount' => ['sometimes', 'integer', 'min:1'],
            'achieved_amount' => ['sometimes', 'integer', 'min:0'],
            'period_start' => ['sometimes', 'date'],
            'period_end' => ['sometimes', 'date', 'after_or_equal:period_start'],
            'status' => ['sometimes', Rule::in([GoalStatus::Active->value, GoalStatus::Completed->value])],
        ];
    }
}
