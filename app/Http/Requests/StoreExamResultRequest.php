<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\DTOs\ExamResultData;
use App\Enums\ExamResultStatus;
use App\Models\ExamResult;
use App\Models\ExamSection;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * يتحقق من صحة بيانات تسجيل نتيجة طالب في امتحان (draft/submitted).
 * authorize() يفوّض إلى ExamResultPolicy::create.
 */
class StoreExamResultRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('create', ExamResult::class);
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $organizationId = $this->user()->organization_id;

        return [
            'exam_id' => [
                'required',
                'integer',
                Rule::exists('exams', 'id')->where('organization_id', $organizationId),
            ],
            'exam_section_id' => [
                'required',
                'integer',
                Rule::exists('exam_sections', 'id')->where('organization_id', $organizationId),
            ],
            'student_id' => [
                'required',
                'integer',
                Rule::exists('student_profiles', 'id')->where('organization_id', $organizationId),
            ],
            'score' => ['required', 'integer', 'min:0'],
            'grade' => ['nullable', 'string', 'max:50'],
            // القيمتان المسموحتان فقط عند التسجيل: الاعتماد يمر عبر action مستقل
            // (ApproveExamResultAction)، فلا يمكن تجاوز approved_by/approved_at.
            'status' => ['required', Rule::in([
                ExamResultStatus::Draft->value,
                ExamResultStatus::Submitted->value,
            ])],
        ];
    }

    /**
     * DTO مكتوب الأنواع — لا يستقبل الـ Action مصفوفة حرّة.
     */
    public function toData(): ExamResultData
    {
        return ExamResultData::fromArray($this->validated());
    }

    public function withValidator($validator): void
    {
        // تكامل البيانات (§3): يجب أن ينتمي exam_section_id فعلياً إلى exam_id
        // المحدد — يمنع ربط نتيجة بقسم تابع لامتحان آخر (Integrity Check).
        $validator->after(function ($validator) {
            $examId = $this->integer('exam_id');
            $sectionId = $this->integer('exam_section_id');

            if (! $examId || ! $sectionId) {
                return;
            }

            $belongsToExam = ExamSection::where('id', $sectionId)
                ->where('exam_id', $examId)
                ->exists();

            if (! $belongsToExam) {
                $validator->errors()->add('exam_section_id', __('messages.exam_results.section_mismatch'));
            }
        });
    }
}
