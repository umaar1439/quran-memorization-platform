<?php

declare(strict_types=1);

namespace App\DTOs;

use App\Enums\UserStatus;

/**
 * بيانات مُصادَق عليها لإنشاء/تحديث مستخدم إداري، تُبنى من
 * FormRequest::validated() وتُمرَّر إلى الـ Actions بديلاً عن المصفوفات
 * الحرة (Loose Arrays) — نفس نمط TeacherData/StudentData.
 *
 * لا تحمل organization_id: هذا الحقل يُشتق من سياق المسؤول الحالي فقط
 * (منع Privilege Escalation)، ولا يُقبل من العميل إطلاقاً.
 */
final readonly class UserData
{
    public function __construct(
        public string $name,
        public string $email,
        public ?string $password,
        public ?string $phone,
        public string $role,
        public UserStatus $status,
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            name: (string) $data['name'],
            email: (string) $data['email'],
            password: self::nullableString($data['password'] ?? null),
            phone: self::nullableString($data['phone'] ?? null),
            role: (string) $data['role'],
            status: UserStatus::from((string) $data['status']),
        );
    }

    /**
     * كلمة المرور أُرسلت فعلاً؟ (في التعديل: لا تغيير عند ترك الحقل فارغاً).
     */
    public function hasPassword(): bool
    {
        return $this->password !== null;
    }

    /**
     * الحقول القابلة للتعبئة الجماعية على User::$fillable — الدور والحالة
     * وكلمة المرور تُعيَّن صريحاً في الـ Action وليست جزءاً من هذه القائمة.
     *
     * @return array{name: string, email: string, phone: string|null}
     */
    public function attributes(): array
    {
        return [
            'name' => $this->name,
            'email' => $this->email,
            'phone' => $this->phone,
        ];
    }

    /**
     * الحقل النصي الاختياري: الفراغ ('' بعد ConvertEmptyStringsToNull) يعني
     * "غير مُرسَل" لا "امسح القيمة" — يمنع مسح الهاتف/كلمة المرور بالخطأ.
     */
    private static function nullableString(mixed $value): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }

        return (string) $value;
    }
}
