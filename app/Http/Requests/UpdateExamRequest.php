<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\DTOs\ExamUpdateData;
use App\Enums\ExamStatus;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * يتحقق من صحة بيانات تعديل امتحان. authorize() يفوّض إلى
 * ExamPolicy::update التي تمنع التعديل بعد status=approved (§5).
 */
class UpdateExamRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('update', $this->route('exam'));
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'title' => ['sometimes', 'string', 'max:255'],
            'exam_type' => ['sometimes', 'string', 'max:100'],
            'scheduled_at' => ['nullable', 'date'],
            'status' => ['sometimes', Rule::enum(ExamStatus::class)],
        ];
    }

    /**
     * DTO جزئي: الحقول الغائبة تبقى كما هي، و`scheduled_at=null` صريح يمسحها.
     */
    public function toData(): ExamUpdateData
    {
        return ExamUpdateData::fromArray($this->validated());
    }
}
