<?php

namespace Database\Seeders;

use App\Models\Contract;
use App\Models\Ticket;
use Illuminate\Database\Seeder;

class TicketSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $contracts = Contract::with('vendor')->get();

        if ($contracts->isEmpty()) {
            $this->command->warn('No contracts found. Please run ContractSeeder first.');
            return;
        }

        $tickets = [
            [
                'number' => 'TKT/2024/001',
                'date' => '2024-01-15',
                'contract_id' => $contracts->where('number', 'CTR/2024/001')->first()->id,
                'vendor_id' => $contracts->where('number', 'CTR/2024/001')->first()->vendor_id,
                'notes' => 'Pembayaran rutin periode Januari 2024',
                'is_active' => true,
            ],
            [
                'number' => 'TKT/2024/002',
                'date' => '2024-02-18',
                'contract_id' => $contracts->where('number', 'CTR/2024/002')->first()->id,
                'vendor_id' => $contracts->where('number', 'CTR/2024/002')->first()->vendor_id,
                'notes' => 'Pembayaran rutin periode Februari 2024',
                'is_active' => true,
            ],
            [
                'number' => 'TKT/2024/003',
                'date' => '2024-03-10',
                'contract_id' => $contracts->where('number', 'CTR/2024/003')->first()->id,
                'vendor_id' => $contracts->where('number', 'CTR/2024/003')->first()->vendor_id,
                'notes' => 'Pembayaran rutin periode Maret 2024',
                'is_active' => true,
            ],
            [
                'number' => 'TKT/2024/004',
                'date' => '2024-04-15',
                'contract_id' => $contracts->where('number', 'CTR/2024/004')->first()->id,
                'vendor_id' => $contracts->where('number', 'CTR/2024/004')->first()->vendor_id,
                'notes' => 'Pembayaran termin 1 - Mobilisasi peralatan',
                'is_active' => true,
            ],
            [
                'number' => 'TKT/2024/005',
                'date' => '2024-05-25',
                'contract_id' => $contracts->where('number', 'CTR/2024/005')->first()->id,
                'vendor_id' => $contracts->where('number', 'CTR/2024/005')->first()->vendor_id,
                'notes' => 'Pembayaran termin 1 - Pekerjaan persiapan',
                'is_active' => true,
            ],
            [
                'number' => 'TKT/2024/006',
                'date' => '2024-05-20',
                'contract_id' => $contracts->where('number', 'CTR/2024/004')->first()->id,
                'vendor_id' => $contracts->where('number', 'CTR/2024/004')->first()->vendor_id,
                'notes' => 'Pembayaran termin 2 - Pekerjaan pondasi',
                'is_active' => true,
            ],
            [
                'number' => 'TKT/2024/007',
                'date' => '2024-06-12',
                'contract_id' => $contracts->where('number', 'CTR/2024/006')->first()->id,
                'vendor_id' => $contracts->where('number', 'CTR/2024/006')->first()->vendor_id,
                'notes' => 'Pembayaran rutin periode Juni 2024',
                'is_active' => true,
            ],
            [
                'number' => 'TKT/2024/008',
                'date' => '2024-06-22',
                'contract_id' => $contracts->where('number', 'CTR/2024/004')->first()->id,
                'vendor_id' => $contracts->where('number', 'CTR/2024/004')->first()->vendor_id,
                'notes' => 'Pembayaran termin 3 - Pekerjaan struktur',
                'is_active' => true,
            ],
            [
                'number' => 'TKT/2024/009',
                'date' => '2024-07-05',
                'contract_id' => $contracts->where('number', 'CTR/2024/005')->first()->id,
                'vendor_id' => $contracts->where('number', 'CTR/2024/005')->first()->vendor_id,
                'notes' => 'Pembayaran termin 2 - Instalasi kabel',
                'is_active' => true,
            ],
            [
                'number' => 'TKT/2024/010',
                'date' => '2024-07-20',
                'contract_id' => $contracts->where('number', 'CTR/2024/007')->first()->id,
                'vendor_id' => $contracts->where('number', 'CTR/2024/007')->first()->vendor_id,
                'notes' => 'Pembayaran termin 1 - Pekerjaan awal',
                'is_active' => true,
            ],
            [
                'number' => 'TKT/2024/011',
                'date' => '2024-07-28',
                'contract_id' => $contracts->where('number', 'CTR/2024/004')->first()->id,
                'vendor_id' => $contracts->where('number', 'CTR/2024/004')->first()->vendor_id,
                'notes' => 'Pembayaran termin 4 - Pekerjaan finishing',
                'is_active' => true,
            ],
            [
                'number' => 'TKT/2024/012',
                'date' => '2024-08-10',
                'contract_id' => $contracts->where('number', 'CTR/2024/007')->first()->id,
                'vendor_id' => $contracts->where('number', 'CTR/2024/007')->first()->vendor_id,
                'notes' => 'Pembayaran termin 2 - Progress 35%',
                'is_active' => true,
            ],
            [
                'number' => 'TKT/2024/013',
                'date' => '2024-08-25',
                'contract_id' => $contracts->where('number', 'CTR/2024/008')->first()->id,
                'vendor_id' => $contracts->where('number', 'CTR/2024/008')->first()->vendor_id,
                'notes' => 'Pembayaran rutin periode Agustus 2024',
                'is_active' => true,
            ],
            [
                'number' => 'TKT/2024/014',
                'date' => '2024-09-05',
                'contract_id' => $contracts->where('number', 'CTR/2024/005')->first()->id,
                'vendor_id' => $contracts->where('number', 'CTR/2024/005')->first()->vendor_id,
                'notes' => 'Pembayaran termin 3 - Pengujian sistem',
                'is_active' => true,
            ],
            [
                'number' => 'TKT/2024/015',
                'date' => '2024-09-15',
                'contract_id' => $contracts->where('number', 'CTR/2024/009')->first()->id,
                'vendor_id' => $contracts->where('number', 'CTR/2024/009')->first()->vendor_id,
                'notes' => 'Pembayaran termin 1 - Down payment',
                'is_active' => true,
            ],
            [
                'number' => 'TKT/2024/016',
                'date' => '2024-10-08',
                'contract_id' => $contracts->where('number', 'CTR/2024/007')->first()->id,
                'vendor_id' => $contracts->where('number', 'CTR/2024/007')->first()->vendor_id,
                'notes' => 'Pembayaran termin 3 - Progress 55%',
                'is_active' => true,
            ],
            [
                'number' => 'TKT/2024/017',
                'date' => '2024-10-22',
                'contract_id' => $contracts->where('number', 'CTR/2024/010')->first()->id,
                'vendor_id' => $contracts->where('number', 'CTR/2024/010')->first()->vendor_id,
                'notes' => 'Pembayaran rutin periode Oktober 2024',
                'is_active' => true,
            ],
            [
                'number' => 'TKT/2024/018',
                'date' => '2024-11-05',
                'contract_id' => $contracts->where('number', 'CTR/2024/009')->first()->id,
                'vendor_id' => $contracts->where('number', 'CTR/2024/009')->first()->vendor_id,
                'notes' => 'Pembayaran termin 2 - Progress 70%',
                'is_active' => true,
            ],
            [
                'number' => 'TKT/2024/019',
                'date' => '2024-11-28',
                'contract_id' => $contracts->where('number', 'CTR/2024/011')->first()->id,
                'vendor_id' => $contracts->where('number', 'CTR/2024/011')->first()->vendor_id,
                'notes' => 'Pembayaran termin 1 - Mobilisasi',
                'is_active' => true,
            ],
            [
                'number' => 'TKT/2024/020',
                'date' => '2024-12-10',
                'contract_id' => $contracts->where('number', 'CTR/2024/012')->first()->id,
                'vendor_id' => $contracts->where('number', 'CTR/2024/012')->first()->vendor_id,
                'notes' => 'Pembayaran rutin periode Desember 2024',
                'is_active' => true,
            ],
            [
                'number' => 'TKT/2025/001',
                'date' => '2025-01-12',
                'contract_id' => $contracts->where('number', 'CTR/2025/001')->first()->id,
                'vendor_id' => $contracts->where('number', 'CTR/2025/001')->first()->vendor_id,
                'notes' => 'Pembayaran termin 1 - Pekerjaan tahap awal',
                'is_active' => true,
            ],
            [
                'number' => 'TKT/2025/002',
                'date' => '2025-01-18',
                'contract_id' => $contracts->where('number', 'CTR/2025/002')->first()->id,
                'vendor_id' => $contracts->where('number', 'CTR/2025/002')->first()->vendor_id,
                'notes' => 'Pembayaran rutin periode Januari 2025',
                'is_active' => true,
            ],
        ];

        foreach ($tickets as $ticket) {
            Ticket::create($ticket);
        }
    }
}
