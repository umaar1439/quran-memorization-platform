<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\RecitationRating;
use App\Enums\RecitationType;
use App\Models\Recitation;
use App\Models\StudentProfile;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Recitation>
 */
class RecitationFactory extends Factory
{
    public function definition(): array
    {
        return [
            'student_id' => StudentProfile::factory(),
            'type' => fake()->randomElement(RecitationType::cases()),
            'surah_number' => fake()->numberBetween(1, 114),
            'ayah_from' => 1,
            'ayah_to' => fake()->numberBetween(2, 20),
            'rating' => fake()->randomElement(RecitationRating::cases()),
            'notes' => fake()->optional()->sentence(),
            'performed_at' => fake()->dateTimeBetween('-1 month', 'now'),
            'idempotency_key' => (string) Str::uuid(),
        ];
    }

    /**
     * يضمن اتساق organization_id/mosque_id/circle_id/teacher_id مع الطالب الفعلي
     * عند عدم تمرير override صريح لها.
     */
    public function configure(): static
    {
        return $this->afterMaking(function (Recitation $recitation) {
            if ($recitation->student_id) {
                $student = StudentProfile::withoutGlobalScopes()->find($recitation->student_id);

                if ($student) {
                    $recitation->organization_id ??= $student->organization_id;
                    $recitation->mosque_id ??= $student->mosque_id;
                    $recitation->circle_id ??= $student->circle_id;
                }
            }

            if (! $recitation->teacher_id) {
                $recitation->teacher_id = User::factory()->create([
                    'organization_id' => $recitation->organization_id,
                ])->id;
            }
        });
    }
}
