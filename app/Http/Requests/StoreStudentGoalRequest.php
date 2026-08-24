<?php

namespace App\Http\Requests;

use App\Enums\GoalTargetType;
use App\Enums\PeriodType;
use App\Models\StudentGoal;
use App\Models\StudentProfile;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * يتحقق من صحة بيانات إنشاء هدف طالب جديد. authorize() يفوّض إلى
 * StudentGoalPolicy::create التي تتحقق من عضوية الحلقة (IDOR Defense)
 * مثل StoreRecitationRequest.
 */
class StoreStudentGoalRequest extends FormRequest
{
    public function authorize(): bool
    {
        $student = $this->input('student_id')
            ? StudentProfile::find($this->input('student_id'))
            : null;

        return $this->user()->can('create', [StudentGoal::class, $student]);
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'student_id' => ['required', 'integer', Rule::exists('student_profiles', 'id')],
            'period_type' => ['required', Rule::enum(PeriodType::class)],
            'target_type' => ['required', Rule::enum(GoalTargetType::class)],
            'surah_id' => ['nullable', 'integer', Rule::exists('surahs', 'id')],
            'juz_number' => ['nullable', 'integer', 'min:1', 'max:30'],
            'target_amount' => ['required', 'integer', 'min:1'],
            'period_start' => ['required', 'date'],
            'period_end' => ['required', 'date', 'after_or_equal:period_start'],
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            if (! $this->input('surah_id') && ! $this->input('juz_number')) {
                $validator->errors()->add('surah_id', 'يجب تحديد نطاق الهدف: سورة أو جزء.');
            }
        });
    }
}
