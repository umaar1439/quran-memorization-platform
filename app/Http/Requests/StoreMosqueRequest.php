<?php

namespace App\Http\Requests;

use App\Enums\GenericStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * التحقق من إنشاء/تعديل مسجد. authorize() يفوّض لـ MosquePolicy::create/update.
 * organization_id لا يُقبل من العميل — يُضبط في Controller من المستخدم الحالي
 * (منع Privilege Escalation، انظر نمط User::$fillable).
 */
class StoreMosqueRequest extends FormRequest
{
    public function authorize(): bool
    {
        $mosque = $this->route('mosque');

        return $mosque
            ? $this->user()->can('update', $mosque)
            : $this->user()->can('create', \App\Models\Mosque::class);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'address' => ['nullable', 'string', 'max:500'],
            'city' => ['nullable', 'string', 'max:120'],
            'phone' => ['nullable', 'string', 'max:30'],
            'status' => ['required', Rule::enum(GenericStatus::class)],
        ];
    }
}
