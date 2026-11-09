<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\DTOs\StudentData;
use App\Enums\GenericStatus;
use App\Enums\StudentStatus;
use App\Models\StudentProfile;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

/**
 * التحقق من إنشاء طالب وربطه بحلقة تنتمي حصراً لمنظمة المسؤول الحالي.
 * mosque_id يُشتق من الحلقة داخل CreateStudentAction (لا يُقبل من العميل).
 *
 * IDOR / سلامة بيانات: `Rule::exists` يتجاهل Soft Deletes افتراضياً، فكان
 * يقبل حلقة محذوفة. هنا يُشترط أن تكون الحلقة من نفس المنظمة، غير محذوفة،
 * ونشطة — لا نُسند طالباً إلى حلقة معطّلة.
 *
 * رقم القيد فريد داخل المنظمة فقط (فهرس مركّب organization_id+enrollment_number)،
 * فقاعدة التفرد مقيَّدة بالمنظمة نفسها حتى تطابق فهرس قاعدة البيانات تماماً.
 */
class StoreStudentRequest extends FormRequest
{
    public function authorize(): bool
    {
        // Fail-Closed: فاعل بلا سياق منظمة لا يمكنه نسبة طالب لأي مستأجر.
        return $this->user()->organization_id !== null
            && $this->user()->can('create', StudentProfile::class);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $organizationId = $this->user()->organization_id;

        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')],
            'password' => ['required', Password::defaults()],
            'circle_id' => [
                'required',
                'integer',
                Rule::exists('circles', 'id')
                    ->where('organization_id', $organizationId)
                    ->where('status', GenericStatus::Active->value)
                    ->whereNull('deleted_at'),
            ],
            'enrollment_number' => [
                'required',
                'string',
                'max:50',
                Rule::unique('student_profiles', 'enrollment_number')
                    ->where('organization_id', $organizationId),
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
}
