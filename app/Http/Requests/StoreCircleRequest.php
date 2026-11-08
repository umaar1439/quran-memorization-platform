<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Enums\GenericStatus;
use App\Models\Circle;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * التحقق من إنشاء/تعديل حلقة. authorize() يفوّض لـ CirclePolicy::create/update.
 * mosque_id و teacher_id يُتحقق من انتمائهما لنفس منظمة المستخدم (IDOR Defense).
 */
class StoreCircleRequest extends FormRequest
{
    public function authorize(): bool
    {
        $circle = $this->route('circle');

        return $circle
            ? $this->user()->can('update', $circle)
            : $this->user()->can('create', Circle::class);
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
                Rule::exists('mosques', 'id')->where('organization_id', $organizationId),
            ],
            'teacher_id' => [
                'nullable',
                'integer',
                Rule::exists('users', 'id')->where('organization_id', $organizationId),
            ],
            'name' => ['required', 'string', 'max:255'],
            'level' => ['nullable', 'string', 'max:120'],
            'capacity' => ['nullable', 'integer', 'min:1', 'max:500'],
            'status' => ['required', Rule::enum(GenericStatus::class)],
        ];
    }
}
