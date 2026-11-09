<?php

declare(strict_types=1);

namespace App\DTOs;

use App\Enums\ExamSectionType;

/**
 * قسم واحد داخل امتحان (surah/ayah range + type + max_score).
 *
 * قسم مكتوب الأنواع يُبنى من StoreExamRequest::toData()؛ يقبل `section_type`
 * كنص (من validated()) أو كـ Enum (استدعاء مباشر من الاختبارات/الأوامر).
 */
final readonly class ExamSectionData
{
    public function __construct(
        public int $surahNumber,
        public int $ayahFrom,
        public int $ayahTo,
        public ExamSectionType $sectionType,
        public int $maxScore,
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            surahNumber: (int) $data['surah_number'],
            ayahFrom: (int) $data['ayah_from'],
            ayahTo: (int) $data['ayah_to'],
            sectionType: $data['section_type'] instanceof ExamSectionType
                ? $data['section_type']
                : ExamSectionType::from((string) $data['section_type']),
            maxScore: (int) $data['max_score'],
        );
    }
}
