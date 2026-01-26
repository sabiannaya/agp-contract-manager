<?php

namespace Database\Seeders;

use App\Models\Contract;
use App\Models\Vendor;
use Illuminate\Database\Seeder;

class ContractSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $vendors = Vendor::all();

        if ($vendors->isEmpty()) {
            $this->command->warn('No vendors found. Please run VendorSeeder first.');
            return;
        }

        $contracts = [
            // Routine contracts
            [
                'number' => 'CTR/2024/001',
                'date' => '2024-01-10',
                'vendor_id' => $vendors->where('code', 'VND001')->first()->id,
                'amount' => 150000000,
                'cooperation_type' => 'routine',
                'term_count' => null,
                'term_percentages' => null,
                'is_active' => true,
            ],
            [
                'number' => 'CTR/2024/002',
                'date' => '2024-02-15',
                'vendor_id' => $vendors->where('code', 'VND002')->first()->id,
                'amount' => 85000000,
                'cooperation_type' => 'routine',
                'term_count' => null,
                'term_percentages' => null,
                'is_active' => true,
            ],
            [
                'number' => 'CTR/2024/003',
                'date' => '2024-03-05',
                'vendor_id' => $vendors->where('code', 'VND003')->first()->id,
                'amount' => 220000000,
                'cooperation_type' => 'routine',
                'term_count' => null,
                'term_percentages' => null,
                'is_active' => true,
            ],
            
            // Progress-based contracts with term percentages
            [
                'number' => 'CTR/2024/004',
                'date' => '2024-04-12',
                'vendor_id' => $vendors->where('code', 'VND004')->first()->id,
                'amount' => 500000000,
                'cooperation_type' => 'progress',
                'term_count' => 5,
                'term_percentages' => [20, 20, 20, 20, 20], // 5 terms, equal distribution
                'is_active' => true,
            ],
            [
                'number' => 'CTR/2024/005',
                'date' => '2024-05-20',
                'vendor_id' => $vendors->where('code', 'VND005')->first()->id,
                'amount' => 350000000,
                'cooperation_type' => 'progress',
                'term_count' => 4,
                'term_percentages' => [30, 30, 20, 20], // 4 terms, front-loaded
                'is_active' => true,
            ],
            [
                'number' => 'CTR/2024/006',
                'date' => '2024-06-08',
                'vendor_id' => $vendors->where('code', 'VND006')->first()->id,
                'amount' => 175000000,
                'cooperation_type' => 'routine',
                'term_count' => null,
                'term_percentages' => null,
                'is_active' => true,
            ],
            [
                'number' => 'CTR/2024/007',
                'date' => '2024-07-15',
                'vendor_id' => $vendors->where('code', 'VND007')->first()->id,
                'amount' => 420000000,
                'cooperation_type' => 'progress',
                'term_count' => 6,
                'term_percentages' => [15, 15, 20, 20, 15, 15], // 6 terms, balanced
                'is_active' => true,
            ],
            [
                'number' => 'CTR/2024/008',
                'date' => '2024-08-22',
                'vendor_id' => $vendors->where('code', 'VND008')->first()->id,
                'amount' => 95000000,
                'cooperation_type' => 'routine',
                'term_count' => null,
                'term_percentages' => null,
                'is_active' => true,
            ],
            [
                'number' => 'CTR/2024/009',
                'date' => '2024-09-10',
                'vendor_id' => $vendors->where('code', 'VND009')->first()->id,
                'amount' => 280000000,
                'cooperation_type' => 'progress',
                'term_count' => 3,
                'term_percentages' => [40, 30, 30], // 3 terms, front-loaded
                'is_active' => true,
            ],
            [
                'number' => 'CTR/2024/010',
                'date' => '2024-10-18',
                'vendor_id' => $vendors->where('code', 'VND010')->first()->id,
                'amount' => 125000000,
                'cooperation_type' => 'routine',
                'term_count' => null,
                'term_percentages' => null,
                'is_active' => true,
            ],
            [
                'number' => 'CTR/2024/011',
                'date' => '2024-11-25',
                'vendor_id' => $vendors->where('code', 'VND001')->first()->id,
                'amount' => 650000000,
                'cooperation_type' => 'progress',
                'term_count' => 8,
                'term_percentages' => [10, 15, 15, 15, 15, 10, 10, 10], // 8 terms
                'is_active' => true,
            ],
            [
                'number' => 'CTR/2024/012',
                'date' => '2024-12-05',
                'vendor_id' => $vendors->where('code', 'VND002')->first()->id,
                'amount' => 195000000,
                'cooperation_type' => 'routine',
                'term_count' => null,
                'term_percentages' => null,
                'is_active' => true,
            ],
            [
                'number' => 'CTR/2025/001',
                'date' => '2025-01-08',
                'vendor_id' => $vendors->where('code', 'VND003')->first()->id,
                'amount' => 380000000,
                'cooperation_type' => 'progress',
                'term_count' => 4,
                'term_percentages' => [25, 25, 25, 25], // 4 terms, equal
                'is_active' => true,
            ],
            [
                'number' => 'CTR/2025/002',
                'date' => '2025-01-15',
                'vendor_id' => $vendors->where('code', 'VND004')->first()->id,
                'amount' => 145000000,
                'cooperation_type' => 'routine',
                'term_count' => null,
                'term_percentages' => null,
                'is_active' => true,
            ],
            [
                'number' => 'CTR/2023/099',
                'date' => '2023-12-10',
                'vendor_id' => $vendors->where('code', 'VND011')->first()->id,
                'amount' => 75000000,
                'cooperation_type' => 'routine',
                'term_count' => null,
                'term_percentages' => null,
                'is_active' => false, // Inactive contract
            ],
        ];

        foreach ($contracts as $contract) {
            Contract::create($contract);
        }
    }
}
