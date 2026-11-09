<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\DTOs\StudentData;
use App\Enums\GenericStatus;
use App\Enums\StudentStatus;
use App\Models\StudentProfile;
use Closure;
use Illuminate\Database\Query\Builder as QueryBuilder;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

/**
 * التحقق من تعديل طالب ونقله بين الحلقات. الحلقة الجديدة يجب أن تنتمي
 * حصراً لمنظمة المسؤول الحالي وأن تكون غير محذوفة.
 *
 * استثناء مقصود: الحلقة الحالية للطالب تُقبل حتى لو صارت غير نشطة، وإلا
 * تعذّر تعديل اسم طالب لمجرد تعطيل حلقته. أي حلقة *أخرى* يجب أن تكون نشطة.
 *
 * كلمة المرور اختيارية هنا (فارغة = لا تغيير)، بخلاف StoreStudentRequest.
 */
class UpdateStudentRequest extends FormRequest
{
    public function authorize(): bool
    {
        $student = $this->route('student');

        return $student instanceof StudentProfile
            && $this->user()->can('update', $student);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $student = $this->route('student');
        $studentId = $student instanceof StudentProfile ? $student->id : null;
        $currentCircleId = $student instanceof StudentProfile ? $student->circle_id : null;
        $organizationId = $this->user()->organization_id;

        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => [
                'required',
                'email',
                'max:255',
                Rule::unique('users', 'email')->ignore($student instanceof StudentProfile ? $student->user_id : null),
            ],
            // `sometimes` لأن النموذج يرسل الحقل فارغاً عند عدم تغيير كلمة المرور.
            'password' => ['sometimes', 'nullable', Password::defaults()],
            'circle_id' => [
                'required',
                'integer',
                Rule::exists('circles', 'id')
                    ->where('organization_id', $organizationId)
                    ->whereNull('deleted_at')
                    ->where($this->activeOrCurrentCircle($currentCircleId)),
            ],
            'enrollment_number' => [
                'required',
                'string',
                'max:50',
                Rule::unique('student_profiles', 'enrollment_number')
                    ->where('organization_id', $organizationId)
                    ->ignore($studentId),
            ],
            'birth_date' => ['nullable', 'date'],
            'current_juz' => ['nullable', 'integer', 'min:1', 'max:30'],
            'status' => ['required', Rule::enum(StudentStatus::class)],
        ];
    }

    /**
     * تحويل البيانات المُصادَق عليها إلى DTO لتمريرها إلى الـ Action.
     */
    public function toData(): StudentData
    {
        return StudentData::fromArray($this->validated());
    }

    /**
     * شرط مجموعة: الحلقة نشطة، أو هي الحلقة الحالية للطالب (غير النشطة).
     */
    private function activeOrCurrentCircle(?int $currentCircleId): Closure
    {
        return function (QueryBuilder $query) use ($currentCircleId): void {
            $query->where('status', GenericStatus::Active->value);

            if ($currentCircleId !== null) {
                $query->orWhere('id', $currentCircleId);
            }
        };
    }
}
