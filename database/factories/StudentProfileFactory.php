<?php

namespace Database\Factories;

use App\Enums\StudentStatus;
use App\Models\Circle;
use App\Models\StudentProfile;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<\App\Models\StudentProfile>
 */
class StudentProfileFactory extends Factory
{
    public function definition(): array
    {
        return [
            'circle_id' => Circle::factory(),
            'enrollment_number' => fake()->unique()->numerify('STU-#####'),
            'birth_date' => fake()->dateTimeBetween('-18 years', '-6 years'),
            'current_juz' => fake()->numberBetween(1, 30),
            'status' => StudentStatus::Active,
        ];
    }

    /**
     * يضمن اتساق organization_id/mosque_id/user_id مع الحلقة الفعلية
     * عند عدم تمرير override صريح لها.
     */
    public function configure(): static
    {
        return $this->afterMaking(function (StudentProfile $profile) {
            if ($profile->circle_id) {
                $circle = Circle::withoutGlobalScopes()->find($profile->circle_id);

                if ($circle) {
                    $profile->organization_id ??= $circle->organization_id;
                    $profile->mosque_id ??= $circle->mosque_id;
                }
            }

            if (! $profile->user_id) {
                $profile->user_id = User::factory()->create([
                    'organization_id' => $profile->organization_id,
                ])->id;
            }
        });
    }
}
