<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\DTOs\MosqueData;
use App\Enums\GenericStatus;
use App\Models\Mosque;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * التحقق من إنشاء/تعديل مسجد. authorize() يفوّض لـ MosquePolicy::create/update.
 * organization_id لا يُقبل من العميل — يُشتق من الفاعل في CreateMosqueAction
 * (منع Privilege Escalation، انظر نمط User::$fillable).
 *
 * صنف واحد للإنشاء والتعديل لأن القواعد متطابقة تماماً؛ فصل UpdateMosqueRequest
 * بلا اختلاف في القواعد مجرّد تكرار (بخلاف UpdateUserRequest حيث كلمة المرور
 * اختيارية في التعديل).
 */
class StoreMosqueRequest extends FormRequest
{
    public function authorize(): bool
    {
        $mosque = $this->route('mosque');

        if ($mosque) {
            return $this->user()->can('update', $mosque);
        }

        // Fail-Closed: فاعل بلا سياق منظمة (super-admin خارج سياق فريق) لا
        // يملك منظمة تُنسب إليها المساجد الجديدة — 403 صريح بدل 500 لاحقاً.
        return $this->user()->organization_id !== null
            && $this->user()->can('create', Mosque::class);
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

    /**
     * تحويل البيانات المُصادَق عليها إلى DTO لتمريرها إلى الـ Action.
     */
    public function toData(): MosqueData
    {
        return MosqueData::fromArray($this->validated());
    }
}
