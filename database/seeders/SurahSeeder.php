<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Surah;
use Illuminate\Database\Seeder;

/**
 * يحمّل بيانات القرآن العالمية (114 سورة) من database/data/surahs.json.
 * جدول عالمي — لا organization_id، يُحمَّل مرة واحدة ويُشارك بين كل المستأجرين.
 */
class SurahSeeder extends Seeder
{
    public function run(): void
    {
        $path = database_path('data/surahs.json');
        $surahs = json_decode(file_get_contents($path), true);

        $pageStart = 1;

        foreach ($surahs as $surah) {
            Surah::query()->updateOrCreate(
                ['number' => $surah['number']],
                [
                    'name_arabic' => $surah['name_arabic'],
                    'name_english' => $surah['name_english'],
                    'revelation_type' => $surah['revelation_type'],
                    'ayah_count' => $surah['ayah_count'],
                    'page_start' => $pageStart,
                ]
            );

            // تقدير تقريبي لصفحة البداية للسورة التالية (ليس دقيقاً مطبعياً، لأغراض تطويرية فقط).
            $pageStart += max(1, intdiv($surah['ayah_count'], 15));
        }
    }
}
