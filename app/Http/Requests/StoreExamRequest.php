<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Models\Exam;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * يتحقق من صحة بيانات إنشاء امتحان جديد (يبدأ دائماً كـ draft).
 * authorize() يفوّض إلى ExamPolicy::create.
 */
class StoreExamRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('create', Exam::class);
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $organizationId = $this->user()->organization_id;

        return [
            'mosque_id' => [
                'nullable',
                'integer',
                Rule::exists('mosques', 'id')->where('organization_id', $organizationId),
            ],
            'circle_id' => [
                'nullable',
                'integer',
                Rule::exists('circles', 'id')->where('organization_id', $organizationId),
            ],
            'title' => ['required', 'string', 'max:255'],
            'exam_type' => ['required', 'string', 'max:100'],
            'scheduled_at' => ['nullable', 'date'],
            'sections' => ['nullable', 'array'],
            'sections.*.surah_number' => ['required_with:sections', 'integer', 'min:1', 'max:114'],
            'sections.*.ayah_from' => ['required_with:sections', 'integer', 'min:1'],
            'sections.*.ayah_to' => ['required_with:sections', 'integer', 'min:1'],
            'sections.*.section_type' => ['required_with:sections', 'string'],
            'sections.*.max_score' => ['required_with:sections', 'integer', 'min:1'],
        ];
    }
}
