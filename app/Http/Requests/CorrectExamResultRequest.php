<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\DTOs\ExamCorrectionData;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

/**
 * يتحقق من صحة بيانات تصحيح نتيجة معتمدة عبر Correction Workflow.
 * authorize() يفوّض إلى ExamResultPolicy::approve (يحتاج exams.approve —
 * §5 دورة حياة البيانات: أي تصحيح يجب أن يمر بنفس صلاحية الاعتماد).
 */
class CorrectExamResultRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('approve', $this->route('exam_result'));
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'score' => ['required', 'integer', 'min:0'],
            'grade' => ['nullable', 'string', 'max:50'],
            'reason' => ['required', 'string', 'max:2000'],
        ];
    }

    /**
     * DTO مكتوب الأنواع — لا يستقبل الـ Action مصفوفة حرّة.
     */
    public function toData(): ExamCorrectionData
    {
        return ExamCorrectionData::fromArray($this->validated());
    }
}
