<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\DTOs\CircleData;
use App\Enums\GenericStatus;
use App\Models\Circle;
use App\Models\User;
use App\Support\RoleHierarchy;
use Closure;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * التحقق من إنشاء/تعديل حلقة. authorize() يفوّض لـ CirclePolicy::create/update.
 *
 * IDOR Defense: mosque_id و teacher_id يجب أن ينتميا لمنظمة الفاعل، ويجب أن
 * يكونا غير محذوفين (Soft Delete) — `Rule::exists` يتجاهل Soft Deletes
 * افتراضياً فيقبل سجلاً محذوفاً.
 *
 * سلامة الإسناد: teacher_id يجب أن يحمل دوراً تعليمياً/إشرافياً
 * (RoleHierarchy::CIRCLE_TEACHER_ROLES)، وإلا رُفض بـ 422 على الحقل نفسه
 * بدل إسناد الحلقة لطالب أو ولي أمر.
 */
class StoreCircleRequest extends FormRequest
{
    public function authorize(): bool
    {
        $circle = $this->route('circle');

        if ($circle) {
            return $this->user()->can('update', $circle);
        }

        // Fail-Closed: بلا سياق منظمة لا نستطيع نسبة الحلقة لأي مستأجر.
        return $this->user()->organization_id !== null
            && $this->user()->can('create', Circle::class);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $organizationId = $this->user()->organization_id;

        return [
            'mosque_id' => [
                'required',
                'integer',
                Rule::exists('mosques', 'id')
                    ->where('organization_id', $organizationId)
                    ->whereNull('deleted_at'),
            ],
            'teacher_id' => [
                'nullable',
                'integer',
                Rule::exists('users', 'id')
                    ->where('organization_id', $organizationId)
                    ->whereNull('deleted_at'),
                $this->teacherRoleRule($organizationId),
            ],
            'name' => ['required', 'string', 'max:255'],
            'level' => ['nullable', 'string', 'max:120'],
            'capacity' => ['nullable', 'integer', 'min:1', 'max:500'],
            'status' => ['required', Rule::enum(GenericStatus::class)],
        ];
    }

    /**
     * تحويل البيانات المُصادَق عليها إلى DTO لتمريرها إلى الـ Action.
     */
    public function toData(): CircleData
    {
        return CircleData::fromArray($this->validated());
    }

    /**
     * يُتحقق أن المستخدم المختار مسؤول حلقة فعلاً. البحث مقيَّد بالمنظمة
     * صراحةً لأن User لا يستخدم BelongsToOrganization (لا Global Scope).
     */
    private function teacherRoleRule(?int $organizationId): Closure
    {
        return function (string $attribute, mixed $value, Closure $fail) use ($organizationId): void {
            if ($value === null || $value === '') {
                return;
            }

            $candidate = User::query()
                ->whereKey($value)
                ->where('organization_id', $organizationId)
                ->first();

            if ($candidate === null || ! $candidate->hasAnyRole(RoleHierarchy::CIRCLE_TEACHER_ROLES)) {
                $fail(__('messages.circles.teacher_role_required'));
            }
        };
    }
}
