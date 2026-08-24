<?php

namespace App\Http\Requests;

use App\Enums\RecitationRating;
use App\Enums\RecitationType;
use App\Models\Recitation;
use App\Models\StudentProfile;
use App\Rules\ValidAyahRange;
use App\Rules\ValidSurahNumber;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * يتحقق من صحة بيانات تسجيل تسميع جديد. authorize() يفوّض للـ
 * RecitationPolicy::create التي تتحقق من عضوية الحلقة (IDOR Defense).
 */
class StoreRecitationRequest extends FormRequest
{
    public function authorize(): bool
    {
        $student = $this->input('student_id')
            ? StudentProfile::find($this->input('student_id'))
            : null;

        return $this->user()->can('create', [Recitation::class, $student]);
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'student_id' => ['required', 'integer', Rule::exists('student_profiles', 'id')],
            'type' => ['required', Rule::enum(RecitationType::class)],
            'surah_number' => ['required', 'integer', new ValidSurahNumber],
            'ayah_from' => ['required', 'integer', 'min:1'],
            'ayah_to' => [
                'required',
                'integer',
                new ValidAyahRange($this->input('surah_number'), $this->input('ayah_from')),
            ],
            'rating' => ['nullable', Rule::enum(RecitationRating::class)],
            'notes' => ['nullable', 'string', 'max:2000'],
            'performed_at' => ['nullable', 'date'],
            'idempotency_key' => ['required', 'uuid'],
        ];
    }
}
