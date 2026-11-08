<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\GenericStatus;
use App\Models\Mosque;
use App\Models\Organization;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Mosque>
 */
class MosqueFactory extends Factory
{
    public function definition(): array
    {
        return [
            'organization_id' => Organization::factory(),
            'name' => 'مسجد '.fake()->lastName(),
            'address' => fake()->address(),
            'city' => fake()->city(),
            'phone' => fake()->numerify('01########'),
            'status' => GenericStatus::Active,
        ];
    }
}
