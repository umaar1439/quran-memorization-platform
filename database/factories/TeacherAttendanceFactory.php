<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Mosque;
use App\Models\TeacherAttendance;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<TeacherAttendance>
 */
class TeacherAttendanceFactory extends Factory
{
    public function definition(): array
    {
        return [
            'mosque_id' => Mosque::factory(),
            'date' => fake()->date(),
            'first_activity_at' => fake()->dateTimeBetween('-1 week', 'now'),
            'last_activity_at' => fake()->dateTimeBetween('-1 week', 'now'),
            'actions_count' => fake()->numberBetween(1, 20),
        ];
    }

    /**
     * يضمن اتساق organization_id/teacher_id مع المسجد الفعلي عند عدم
     * تمرير override صريح لها.
     */
    public function configure(): static
    {
        return $this->afterMaking(function (TeacherAttendance $attendance) {
            if (! $attendance->organization_id && $attendance->mosque_id) {
                $attendance->organization_id = Mosque::withoutGlobalScopes()->find($attendance->mosque_id)?->organization_id;
            }

            if (! $attendance->teacher_id) {
                $attendance->teacher_id = User::factory()->create([
                    'organization_id' => $attendance->organization_id,
                ])->id;
            }
        });
    }
}
