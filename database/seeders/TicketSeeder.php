<?php

namespace Database\Seeders;

use App\Models\Contract;
use App\Models\Ticket;
use App\Models\User;
use Illuminate\Database\Seeder;

class TicketSeeder extends Seeder
{
    /**
     * Seed payment submission tickets.
     *
     * Each ticket is a payment request against a contract. It carries:
     *  - amount            → how much the vendor is requesting
     *  - approval_status   → draft | pending | approved | rejected | paid
     *  - submitted_at / approved_at / paid_at  → workflow timestamps
     *  - reference_no      → bank transfer reference (when paid)
     *
     * We create a realistic mix of statuses so every workflow state is
     * visible during manual testing.
     */
    public function run(): void
    {
        $contracts = Contract::with('vendor')->get();

        if ($contracts->isEmpty()) {
            $this->command->warn('No contracts found. Please run ContractSeeder first.');
            return;
        }

        $admin = User::where('email', 'admin@pln.co.id')->first();
        $kontrak = User::where('email', 'kontrak@pln.co.id')->first();

        $c = fn (string $number) => $contracts->where('number', $number)->first();

        $tickets = [
            // ── CTR/2024/001 (routine, Rp 150 jt) — 2 payments: 1 paid + 1 pending
            [
                'number' => 'TKT/2024/001',
                'date' => '2024-01-15',
                'contract_id' => $c('CTR/2024/001')->id,
                'vendor_id' => $c('CTR/2024/001')->vendor_id,
                'amount' => 50000000,
                'approval_status' => 'paid',
                'submitted_at' => '2024-01-16 08:00:00',
                'approved_at' => '2024-01-18 14:30:00',
                'paid_at' => '2024-01-22 10:00:00',
                'reference_no' => 'TRF-2024-001-001',
                'notes' => 'Pembayaran rutin Januari 2024',
                'status' => 'complete',
                'is_active' => true,
                'created_by_user_id' => $admin->id,
            ],
            [
                'number' => 'TKT/2024/002',
                'date' => '2024-02-18',
                'contract_id' => $c('CTR/2024/001')->id,
                'vendor_id' => $c('CTR/2024/001')->vendor_id,
                'amount' => 50000000,
                'approval_status' => 'pending',
                'submitted_at' => '2024-02-19 09:00:00',
                'notes' => 'Pembayaran rutin Februari 2024',
                'is_active' => true,
                'created_by_user_id' => $admin->id,
            ],

            // ── CTR/2024/002 (routine, Rp 85 jt) — 1 approved (awaiting mark-paid)
            [
                'number' => 'TKT/2024/003',
                'date' => '2024-02-20',
                'contract_id' => $c('CTR/2024/002')->id,
                'vendor_id' => $c('CTR/2024/002')->vendor_id,
                'amount' => 42500000,
                'approval_status' => 'approved',
                'submitted_at' => '2024-02-21 10:00:00',
                'approved_at' => '2024-02-25 16:00:00',
                'notes' => 'Pembayaran rutin Februari 2024 — siap dibayar',
                'status' => 'complete',
                'is_active' => true,
                'created_by_user_id' => $kontrak->id,
            ],

            // ── CTR/2024/003 (routine, Rp 220 jt) — 1 rejected + 1 draft resubmission
            [
                'number' => 'TKT/2024/004',
                'date' => '2024-03-10',
                'contract_id' => $c('CTR/2024/003')->id,
                'vendor_id' => $c('CTR/2024/003')->vendor_id,
                'amount' => 110000000,
                'approval_status' => 'rejected',
                'submitted_at' => '2024-03-11 08:30:00',
                'notes' => 'Pembayaran Maret — ditolak karena dokumen tidak lengkap',
                'is_active' => true,
                'created_by_user_id' => $kontrak->id,
            ],
            [
                'number' => 'TKT/2024/005',
                'date' => '2024-03-20',
                'contract_id' => $c('CTR/2024/003')->id,
                'vendor_id' => $c('CTR/2024/003')->vendor_id,
                'amount' => 110000000,
                'approval_status' => 'draft',
                'replaces_ticket_id' => null, // will be linked in PaymentTrackerSeeder
                'notes' => 'Pengajuan ulang pembayaran Maret — dokumen dilengkapi',
                'is_active' => true,
                'created_by_user_id' => $kontrak->id,
            ],

            // ── CTR/2024/004 (progress 5 terms, Rp 500 jt) — T1 paid, T2 paid, T3 approved, T4 pending, T5 draft
            [
                'number' => 'TKT/2024/006',
                'date' => '2024-04-15',
                'contract_id' => $c('CTR/2024/004')->id,
                'vendor_id' => $c('CTR/2024/004')->vendor_id,
                'amount' => 100000000,  // 20% of 500M
                'approval_status' => 'paid',
                'submitted_at' => '2024-04-16 08:00:00',
                'approved_at' => '2024-04-20 14:00:00',
                'paid_at' => '2024-04-25 10:00:00',
                'reference_no' => 'TRF-2024-004-T1',
                'notes' => 'Termin 1 — Mobilisasi peralatan (20%)',
                'status' => 'complete',
                'is_active' => true,
                'created_by_user_id' => $admin->id,
            ],
            [
                'number' => 'TKT/2024/007',
                'date' => '2024-05-20',
                'contract_id' => $c('CTR/2024/004')->id,
                'vendor_id' => $c('CTR/2024/004')->vendor_id,
                'amount' => 100000000,  // 20%
                'approval_status' => 'paid',
                'submitted_at' => '2024-05-21 08:00:00',
                'approved_at' => '2024-05-25 15:00:00',
                'paid_at' => '2024-05-30 10:00:00',
                'reference_no' => 'TRF-2024-004-T2',
                'notes' => 'Termin 2 — Pekerjaan pondasi (20%)',
                'status' => 'complete',
                'is_active' => true,
                'created_by_user_id' => $admin->id,
            ],
            [
                'number' => 'TKT/2024/008',
                'date' => '2024-06-22',
                'contract_id' => $c('CTR/2024/004')->id,
                'vendor_id' => $c('CTR/2024/004')->vendor_id,
                'amount' => 100000000,  // 20%
                'approval_status' => 'approved',
                'submitted_at' => '2024-06-23 08:00:00',
                'approved_at' => '2024-06-28 14:00:00',
                'notes' => 'Termin 3 — Pekerjaan struktur (20%) — siap bayar',
                'status' => 'complete',
                'is_active' => true,
                'created_by_user_id' => $admin->id,
            ],
            [
                'number' => 'TKT/2024/009',
                'date' => '2024-07-28',
                'contract_id' => $c('CTR/2024/004')->id,
                'vendor_id' => $c('CTR/2024/004')->vendor_id,
                'amount' => 100000000,  // 20%
                'approval_status' => 'pending',
                'submitted_at' => '2024-07-29 08:00:00',
                'notes' => 'Termin 4 — Pekerjaan finishing (20%) — menunggu approval',
                'is_active' => true,
                'created_by_user_id' => $admin->id,
            ],
            [
                'number' => 'TKT/2024/010',
                'date' => '2024-08-15',
                'contract_id' => $c('CTR/2024/004')->id,
                'vendor_id' => $c('CTR/2024/004')->vendor_id,
                'amount' => 100000000,  // 20%
                'approval_status' => 'draft',
                'notes' => 'Termin 5 — Serah terima (20%) — draft belum diajukan',
                'is_active' => true,
                'created_by_user_id' => $admin->id,
            ],

            // ── CTR/2024/005 (progress 4 terms, Rp 350 jt) — T1 paid, T2 pending
            [
                'number' => 'TKT/2024/011',
                'date' => '2024-05-25',
                'contract_id' => $c('CTR/2024/005')->id,
                'vendor_id' => $c('CTR/2024/005')->vendor_id,
                'amount' => 105000000,  // 30% of 350M
                'approval_status' => 'paid',
                'submitted_at' => '2024-05-26 08:00:00',
                'approved_at' => '2024-05-30 14:00:00',
                'paid_at' => '2024-06-05 10:00:00',
                'reference_no' => 'TRF-2024-005-T1',
                'notes' => 'Termin 1 — Pekerjaan persiapan (30%)',
                'status' => 'complete',
                'is_active' => true,
                'created_by_user_id' => $kontrak->id,
            ],
            [
                'number' => 'TKT/2024/012',
                'date' => '2024-07-05',
                'contract_id' => $c('CTR/2024/005')->id,
                'vendor_id' => $c('CTR/2024/005')->vendor_id,
                'amount' => 105000000,  // 30%
                'approval_status' => 'pending',
                'submitted_at' => '2024-07-06 09:00:00',
                'notes' => 'Termin 2 — Instalasi kabel (30%) — menunggu approval',
                'is_active' => true,
                'created_by_user_id' => $kontrak->id,
            ],

            // ── CTR/2024/006 (routine, Rp 175 jt) — 1 paid
            [
                'number' => 'TKT/2024/013',
                'date' => '2024-06-12',
                'contract_id' => $c('CTR/2024/006')->id,
                'vendor_id' => $c('CTR/2024/006')->vendor_id,
                'amount' => 87500000,
                'approval_status' => 'paid',
                'submitted_at' => '2024-06-13 08:00:00',
                'approved_at' => '2024-06-17 15:00:00',
                'paid_at' => '2024-06-20 10:00:00',
                'reference_no' => 'TRF-2024-006-001',
                'notes' => 'Pembayaran rutin Juni 2024',
                'status' => 'complete',
                'is_active' => true,
                'created_by_user_id' => $kontrak->id,
            ],

            // ── CTR/2024/007 (progress 6 terms, Rp 420 jt) — T1 paid, T2 approved, T3 draft
            [
                'number' => 'TKT/2024/014',
                'date' => '2024-07-20',
                'contract_id' => $c('CTR/2024/007')->id,
                'vendor_id' => $c('CTR/2024/007')->vendor_id,
                'amount' => 63000000,  // 15% of 420M
                'approval_status' => 'paid',
                'submitted_at' => '2024-07-21 08:00:00',
                'approved_at' => '2024-07-25 14:00:00',
                'paid_at' => '2024-07-30 10:00:00',
                'reference_no' => 'TRF-2024-007-T1',
                'notes' => 'Termin 1 — Pekerjaan awal (15%)',
                'status' => 'complete',
                'is_active' => true,
                'created_by_user_id' => $admin->id,
            ],
            [
                'number' => 'TKT/2024/015',
                'date' => '2024-08-10',
                'contract_id' => $c('CTR/2024/007')->id,
                'vendor_id' => $c('CTR/2024/007')->vendor_id,
                'amount' => 63000000,  // 15%
                'approval_status' => 'approved',
                'submitted_at' => '2024-08-11 08:00:00',
                'approved_at' => '2024-08-15 14:00:00',
                'notes' => 'Termin 2 — Progress 35% — siap bayar',
                'status' => 'complete',
                'is_active' => true,
                'created_by_user_id' => $admin->id,
            ],
            [
                'number' => 'TKT/2024/016',
                'date' => '2024-10-08',
                'contract_id' => $c('CTR/2024/007')->id,
                'vendor_id' => $c('CTR/2024/007')->vendor_id,
                'amount' => 84000000,  // 20%
                'approval_status' => 'draft',
                'notes' => 'Termin 3 — Progress 55% — draft',
                'is_active' => true,
                'created_by_user_id' => $admin->id,
            ],

            // ── CTR/2024/008 (routine, Rp 95 jt) — 1 draft (no amount yet)
            [
                'number' => 'TKT/2024/017',
                'date' => '2024-08-25',
                'contract_id' => $c('CTR/2024/008')->id,
                'vendor_id' => $c('CTR/2024/008')->vendor_id,
                'amount' => null,
                'approval_status' => 'draft',
                'notes' => 'Pembayaran rutin Agustus — draft, belum ada nominal',
                'is_active' => true,
                'created_by_user_id' => $kontrak->id,
            ],

            // ── CTR/2024/009 (progress 3 terms, Rp 280 jt) — T1 paid, T2 pending
            [
                'number' => 'TKT/2024/018',
                'date' => '2024-09-15',
                'contract_id' => $c('CTR/2024/009')->id,
                'vendor_id' => $c('CTR/2024/009')->vendor_id,
                'amount' => 112000000,  // 40% of 280M
                'approval_status' => 'paid',
                'submitted_at' => '2024-09-16 08:00:00',
                'approved_at' => '2024-09-20 14:00:00',
                'paid_at' => '2024-09-25 10:00:00',
                'reference_no' => 'TRF-2024-009-T1',
                'notes' => 'Termin 1 — Down payment (40%)',
                'status' => 'complete',
                'is_active' => true,
                'created_by_user_id' => $kontrak->id,
            ],
            [
                'number' => 'TKT/2024/019',
                'date' => '2024-11-05',
                'contract_id' => $c('CTR/2024/009')->id,
                'vendor_id' => $c('CTR/2024/009')->vendor_id,
                'amount' => 84000000,   // 30%
                'approval_status' => 'pending',
                'submitted_at' => '2024-11-06 09:00:00',
                'notes' => 'Termin 2 — Progress 70% — menunggu approval',
                'is_active' => true,
                'created_by_user_id' => $kontrak->id,
            ],

            // ── CTR/2024/010 (routine, Rp 125 jt) — 1 draft
            [
                'number' => 'TKT/2024/020',
                'date' => '2024-10-22',
                'contract_id' => $c('CTR/2024/010')->id,
                'vendor_id' => $c('CTR/2024/010')->vendor_id,
                'amount' => 62500000,
                'approval_status' => 'draft',
                'notes' => 'Pembayaran rutin Oktober — draft',
                'is_active' => true,
                'created_by_user_id' => $kontrak->id,
            ],

            // ── CTR/2024/011 (progress 8 terms, Rp 650 jt) — T1 pending
            [
                'number' => 'TKT/2024/021',
                'date' => '2024-11-28',
                'contract_id' => $c('CTR/2024/011')->id,
                'vendor_id' => $c('CTR/2024/011')->vendor_id,
                'amount' => 65000000,   // 10% of 650M
                'approval_status' => 'pending',
                'submitted_at' => '2024-11-29 08:00:00',
                'notes' => 'Termin 1 — Mobilisasi (10%) — menunggu approval',
                'is_active' => true,
                'created_by_user_id' => $admin->id,
            ],

            // ── CTR/2024/012 (routine, Rp 195 jt) — 1 paid
            [
                'number' => 'TKT/2024/022',
                'date' => '2024-12-10',
                'contract_id' => $c('CTR/2024/012')->id,
                'vendor_id' => $c('CTR/2024/012')->vendor_id,
                'amount' => 97500000,
                'approval_status' => 'paid',
                'submitted_at' => '2024-12-11 08:00:00',
                'approved_at' => '2024-12-15 14:00:00',
                'paid_at' => '2024-12-20 10:00:00',
                'reference_no' => 'TRF-2024-012-001',
                'notes' => 'Pembayaran rutin Desember 2024',
                'status' => 'complete',
                'is_active' => true,
                'created_by_user_id' => $kontrak->id,
            ],

            // ── CTR/2025/001 (progress 4 terms, Rp 380 jt) — T1 draft
            [
                'number' => 'TKT/2025/001',
                'date' => '2025-01-12',
                'contract_id' => $c('CTR/2025/001')->id,
                'vendor_id' => $c('CTR/2025/001')->vendor_id,
                'amount' => 95000000,   // 25% of 380M
                'approval_status' => 'draft',
                'notes' => 'Termin 1 — Tahap awal (25%) — draft',
                'is_active' => true,
                'created_by_user_id' => $kontrak->id,
            ],

            // ── CTR/2025/002 (routine, Rp 145 jt) — 1 draft (no amount)
            [
                'number' => 'TKT/2025/002',
                'date' => '2025-01-18',
                'contract_id' => $c('CTR/2025/002')->id,
                'vendor_id' => $c('CTR/2025/002')->vendor_id,
                'amount' => null,
                'approval_status' => 'draft',
                'notes' => 'Pembayaran rutin Januari 2025 — draft, belum ada nominal',
                'is_active' => true,
                'created_by_user_id' => $admin->id,
            ],
        ];

        foreach ($tickets as $ticket) {
            Ticket::create($ticket);
        }

        // Link the re-submission ticket to the rejected one
        $rejected = Ticket::where('number', 'TKT/2024/004')->first();
        $resubmission = Ticket::where('number', 'TKT/2024/005')->first();
        if ($rejected && $resubmission) {
            $resubmission->update(['replaces_ticket_id' => $rejected->id]);
        }
    }
}
