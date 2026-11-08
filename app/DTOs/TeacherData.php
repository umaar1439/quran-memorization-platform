<?php

declare(strict_types=1);

namespace App\DTOs;

use App\Enums\UserStatus;

/**
 * بيانات مُصادَق عليها لإنشاء/تحديث معلم، تُبنى من FormRequest::validated()
 * وتُمرَّر إلى الـ Actions بديلاً عن المصفوفات الحرة (Loose Arrays).
 */
final readonly class TeacherData
{
    /**
     * @param  list<int>  $circleIds
     */
    public function __construct(
        public ?int $userId,
        public ?string $name,
        public ?string $email,
        public ?string $password,
        public ?string $phone,
        public ?string $specialization,
        public ?string $ijazahDetails,
        public ?string $hiredAt,
        public UserStatus $status,
        public array $circleIds,
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            userId: isset($data['user_id']) ? (int) $data['user_id'] : null,
            name: isset($data['name']) ? (string) $data['name'] : null,
            email: isset($data['email']) ? (string) $data['email'] : null,
            password: isset($data['password']) ? (string) $data['password'] : null,
            phone: isset($data['phone']) ? (string) $data['phone'] : null,
            specialization: isset($data['specialization']) ? (string) $data['specialization'] : null,
            ijazahDetails: isset($data['ijazah_details']) ? (string) $data['ijazah_details'] : null,
            hiredAt: isset($data['hired_at']) ? (string) $data['hired_at'] : null,
            status: UserStatus::from((string) $data['status']),
            circleIds: array_values(array_map(
                static fn (mixed $id): int => (int) $id,
                (array) ($data['circles'] ?? []),
            )),
        );
    }
}
