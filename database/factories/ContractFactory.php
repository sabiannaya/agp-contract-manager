<?php

namespace Database\Factories;

use App\Models\Contract;
use App\Models\Vendor;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Contract>
 */
class ContractFactory extends Factory
{
    protected $model = Contract::class;

    public function definition(): array
    {
        return [
            'number' => strtoupper(fake()->unique()->bothify('CTR-####-??')),
            'date' => fake()->date(),
            'vendor_id' => Vendor::factory(),
            'amount' => fake()->randomFloat(2, 10000000, 5000000000),
            'cooperation_type' => 'routine',
            'term_count' => null,
            'term_percentages' => null,
            'is_active' => true,
        ];
    }

    public function progress(int $terms = 3): static
    {
        $percentages = [];
        $remaining = 100;
        for ($i = 0; $i < $terms - 1; $i++) {
            $val = intdiv($remaining, $terms - $i);
            $percentages[] = $val;
            $remaining -= $val;
        }
        $percentages[] = $remaining;

        return $this->state([
            'cooperation_type' => 'progress',
            'term_count' => $terms,
            'term_percentages' => $percentages,
        ]);
    }

    public function routine(): static
    {
        return $this->state([
            'cooperation_type' => 'routine',
            'term_count' => null,
            'term_percentages' => null,
        ]);
    }

    public function inactive(): static
    {
        return $this->state(['is_active' => false]);
    }
}
