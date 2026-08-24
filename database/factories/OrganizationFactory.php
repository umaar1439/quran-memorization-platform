<?php

namespace Database\Factories;

use App\Enums\OrganizationStatus;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<\App\Models\Organization>
 */
class OrganizationFactory extends Factory
{
    public function definition(): array
    {
        $name = fake()->unique()->company();

        return [
            'name' => $name,
            'slug' => Str::slug($name).'-'.fake()->unique()->numberBetween(1000, 9999),
            'status' => fake()->randomElement(OrganizationStatus::cases()),
        ];
    }

    public function active(): static
    {
        return $this->state(['status' => OrganizationStatus::Active]);
    }
}
