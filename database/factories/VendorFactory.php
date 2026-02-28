<?php

namespace Database\Factories;

use App\Models\Vendor;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Vendor>
 */
class VendorFactory extends Factory
{
    protected $model = Vendor::class;

    public function definition(): array
    {
        return [
            'code' => strtoupper(fake()->unique()->bothify('VND-####')),
            'name' => fake()->company(),
            'address' => fake()->address(),
            'join_date' => fake()->date(),
            'contact_person' => fake()->name(),
            'tax_id' => fake()->numerify('##.###.###.#-###.###'),
            'is_active' => true,
        ];
    }

    public function inactive(): static
    {
        return $this->state(['is_active' => false]);
    }
}
