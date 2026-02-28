<?php

namespace Database\Factories;

use App\Models\Contract;
use App\Models\Ticket;
use App\Models\Vendor;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Ticket>
 */
class TicketFactory extends Factory
{
    protected $model = Ticket::class;

    public function definition(): array
    {
        return [
            'number' => strtoupper(fake()->unique()->bothify('TKT-######')),
            'date' => fake()->date(),
            'contract_id' => Contract::factory(),
            'vendor_id' => Vendor::factory(),
            'status' => 'incomplete',
            'amount' => null,
            'approval_status' => 'draft',
            'notes' => fake()->optional()->sentence(),
            'is_active' => true,
        ];
    }

    /**
     * Create a ticket that belongs to a specific contract (auto-sets vendor_id).
     */
    public function forContract(Contract $contract): static
    {
        return $this->state([
            'contract_id' => $contract->id,
            'vendor_id' => $contract->vendor_id,
        ]);
    }

    public function withAmount(float $amount = null): static
    {
        return $this->state([
            'amount' => $amount ?? fake()->randomFloat(2, 1000000, 500000000),
        ]);
    }

    public function draft(): static
    {
        return $this->state(['approval_status' => 'draft']);
    }

    public function pending(): static
    {
        return $this->state([
            'approval_status' => 'pending',
            'submitted_at' => now(),
        ]);
    }

    public function approved(): static
    {
        return $this->state([
            'approval_status' => 'approved',
            'submitted_at' => now()->subDay(),
            'approved_at' => now(),
        ]);
    }

    public function rejected(): static
    {
        return $this->state([
            'approval_status' => 'rejected',
            'submitted_at' => now()->subDay(),
        ]);
    }

    public function paid(): static
    {
        return $this->state([
            'approval_status' => 'paid',
            'submitted_at' => now()->subDays(3),
            'approved_at' => now()->subDays(2),
            'paid_at' => now(),
            'reference_no' => 'REF-' . fake()->numerify('######'),
        ]);
    }

    public function complete(): static
    {
        return $this->state(['status' => 'complete']);
    }

    public function inactive(): static
    {
        return $this->state(['is_active' => false]);
    }
}
