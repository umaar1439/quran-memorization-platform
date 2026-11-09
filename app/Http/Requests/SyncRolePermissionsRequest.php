<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\DTOs\RolePermissionData;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Spatie\Permission\Models\Role;

/**
 * التحقق من مزامنة صلاحيات دور. authorize() يفوّض لـ RolePolicy::update
 * (roles.manage + نفس المنظمة + منع تعديل super-admin)، والقائمة تُقيَّد
 * بأسماء صلاحيات موجودة فعلاً بدل تمرير أي نص إلى Spatie.
 */
class SyncRolePermissionsRequest extends FormRequest
{
    public function authorize(): bool
    {
        $role = $this->route('role');

        return $role instanceof Role && $this->user()->can('update', $role);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'permissions' => ['present', 'array'],
            'permissions.*' => [
                'string',
                Rule::exists('permissions', 'name')->where('guard_name', 'web'),
            ],
        ];
    }

    /**
     * الصلاحيات بعد التطبيع (بلا تكرار) كـ DTO بدل مصفوفة حرة.
     */
    public function toData(): RolePermissionData
    {
        /** @var array<string, mixed> $validated */
        $validated = $this->validated();

        return RolePermissionData::fromArray($validated);
    }
}
