<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\GenericStatus;
use App\Models\Circle;
use App\Models\Mosque;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Circle>
 */
class CircleFactory extends Factory
{
    public function definition(): array
    {
        return [
            'mosque_id' => Mosque::factory(),
            'teacher_id' => null,
            'name' => 'حلقة '.fake()->word(),
            'level' => fake()->randomElement(['مبتدئ', 'متوسط', 'متقدم']),
            'capacity' => fake()->numberBetween(10, 30),
            'status' => GenericStatus::Active,
        ];
    }

    /**
     * لضمان اتساق organization_id مع المسجد الفعلي عند عدم تمرير override صريح.
     */
    public function configure(): static
    {
        return $this->afterMaking(function (Circle $circle) {
            if (! $circle->organization_id && $circle->mosque_id) {
                $circle->organization_id = Mosque::withoutGlobalScopes()->find($circle->mosque_id)?->organization_id;
            }
        });
    }
}
