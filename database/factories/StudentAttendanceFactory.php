<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\AttendanceStatus;
use App\Models\StudentAttendance;
use App\Models\StudentProfile;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<StudentAttendance>
 */
class StudentAttendanceFactory extends Factory
{
    public function definition(): array
    {
        return [
            'student_id' => StudentProfile::factory(),
            'date' => fake()->dateTimeBetween('-1 month', 'now')->format('Y-m-d'),
            'status' => fake()->randomElement(AttendanceStatus::cases()),
            'notes' => fake()->optional()->sentence(),
        ];
    }

    /**
     * يضمن اتساق organization_id/mosque_id/circle_id مع الطالب الفعلي عند عدم
     * تمرير override صريح لها (نفس نمط RecitationFactory).
     */
    public function configure(): static
    {
        return $this->afterMaking(function (StudentAttendance $attendance) {
            if (! $attendance->student_id) {
                return;
            }

            $student = StudentProfile::withoutGlobalScopes()->find($attendance->student_id);

            if ($student) {
                $attendance->organization_id ??= $student->organization_id;
                $attendance->mosque_id ??= $student->mosque_id;
                $attendance->circle_id ??= $student->circle_id;
            }
        });
    }
}
