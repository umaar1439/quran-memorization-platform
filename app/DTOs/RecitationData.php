<?php

declare(strict_types=1);

namespace App\DTOs;

use App\Enums\RecitationRating;
use App\Enums\RecitationType;

/**
 * بيانات مُصادَق عليها لتسجيل تسميع، تُبنى من StoreRecitationRequest::toData()
 * وتُمرَّر إلى RecordRecitationAction بديلاً عن المصفوفة الحرة (Loose Array).
 *
 * لا تعتمد على Eloquent ولا على الطلب: نفس الكائن يُبنى من الطلب أو من
 * الاختبارات أو من أي مستدعٍ آخر، وأي حقل ناقص يظهر كخطأ نوعي لا كخطأ وقت تشغيل.
 */
final readonly class RecitationData
{
    /**
     * @param  list<array{error_type: string, count?: int, notes?: string|null}>  $errors
     */
    public function __construct(
        public int $studentId,
        public RecitationType $type,
        public int $surahNumber,
        public int $ayahFrom,
        public int $ayahTo,
        public ?RecitationRating $rating,
        public ?string $notes,
        public ?string $performedAt,
        public string $idempotencyKey,
        public array $errors = [],
    ) {}

    /**
     * يقبل القيم كنصوص (من validated()) أو كـ Enums (استدعاء مباشر من الاختبارات/طلبات داخلية).
     *
     * @param  array<string, mixed>  $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            studentId: (int) $data['student_id'],
            type: $data['type'] instanceof RecitationType
                ? $data['type']
                : RecitationType::from((string) $data['type']),
            surahNumber: (int) $data['surah_number'],
            ayahFrom: (int) $data['ayah_from'],
            ayahTo: (int) $data['ayah_to'],
            rating: self::rating($data['rating'] ?? null),
            notes: isset($data['notes']) ? (string) $data['notes'] : null,
            performedAt: isset($data['performed_at']) ? (string) $data['performed_at'] : null,
            idempotencyKey: (string) ($data['idempotency_key'] ?? ''),
            errors: array_values((array) ($data['errors'] ?? [])),
        );
    }

    private static function rating(mixed $rating): ?RecitationRating
    {
        if ($rating instanceof RecitationRating) {
            return $rating;
        }

        if ($rating === null || $rating === '') {
            return null;
        }

        return RecitationRating::from((string) $rating);
    }
}
