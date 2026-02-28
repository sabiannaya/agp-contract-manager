<?php

namespace Database\Seeders;

use App\Models\Contract;
use App\Models\ContractApprover;
use App\Models\Ticket;
use App\Models\TicketApprovalStep;
use App\Models\User;
use Illuminate\Database\Seeder;

class PaymentTrackerSeeder extends Seeder
{
    /**
     * Seed the payment-tracker workflow data.
     *
     * This seeder runs AFTER ContractSeeder + TicketSeeder and creates:
     *  1. Contract approvers  — who can approve payment submissions per contract
     *  2. Ticket approval steps — snapshot of approvers for submitted tickets
     *  3. Syncs payment caches on contracts that have paid tickets
     *
     * Scenario overview (for manual testing):
     *
     *  Contract            Approvers                    Tickets with steps
     *  ──────────────────  ───────────────────────────  ───────────────────────
     *  CTR/2024/001        supervisor → manager         TKT/001 paid (both approved)
     *  (routine 150M)                                   TKT/002 pending (step 1 pending)
     *
     *  CTR/2024/002        kontrak → manager            TKT/003 approved (both approved)
     *  (routine 85M)
     *
     *  CTR/2024/003        supervisor → kontrak         TKT/004 rejected (step 1 rejected)
     *  (routine 220M)                                   TKT/005 draft (no steps yet)
     *
     *  CTR/2024/004        supervisor → kontrak → mgr   TKT/006 paid, TKT/007 paid,
     *  (progress 500M)                                  TKT/008 approved, TKT/009 pending
     *                                                   TKT/010 draft
     *
     *  CTR/2024/005        admin → kontrak              TKT/011 paid, TKT/012 pending
     *  (progress 350M)
     *
     *  CTR/2024/006        supervisor                   TKT/013 paid
     *  (routine 175M)
     *
     *  CTR/2024/007        supervisor → kontrak         TKT/014 paid, TKT/015 approved,
     *  (progress 420M)                                  TKT/016 draft
     *
     *  CTR/2024/009        kontrak → supervisor         TKT/018 paid, TKT/019 pending
     *  (progress 280M)
     *
     *  CTR/2024/011        supervisor → kontrak → mgr   TKT/021 pending (step 1 pending)
     *  (progress 650M)
     *
     *  CTR/2024/012        kontrak                      TKT/022 paid
     *  (routine 195M)
     */
    public function run(): void
    {
        $admin      = User::where('email', 'admin@pln.co.id')->first();
        $supervisor = User::where('email', 'supervisor@pln.co.id')->first();
        $kontrak    = User::where('email', 'kontrak@pln.co.id')->first();
        $manager    = User::where('email', 'manager@pln.co.id')->first();
        $john       = User::where('email', 'john@pln.co.id')->first();
        $ken        = User::where('email', 'ken@pln.co.id')->first();
        $sinta      = User::where('email', 'sinta@pln.co.id')->first();

        $contracts = Contract::all()->keyBy('number');
        $tickets   = Ticket::all()->keyBy('number');

        // ────────────────────────────────────────────────────────────────────
        //  1. Contract Approvers
        // ────────────────────────────────────────────────────────────────────

        $approverConfig = [
            'CTR/2024/001' => [
                ['user' => $supervisor, 'remarks' => 'Primary operational verifier'],
                ['user' => $manager, 'remarks' => 'Final finance approval'],
            ],
            'CTR/2024/002' => [
                ['user' => $kontrak, 'remarks' => 'Contract owner review'],
                ['user' => $manager, 'remarks' => 'Budget holder validation'],
            ],
            'CTR/2024/003' => [
                ['user' => $john, 'remarks' => 'Procurement-side document check'],
                ['user' => $ken, 'remarks' => 'Payment readiness confirmation'],
            ],
            'CTR/2024/004' => [
                ['user' => $supervisor, 'remarks' => 'Operational completion verification'],
                ['user' => $sinta, 'remarks' => 'Procurement compliance review'],
                ['user' => $manager, 'remarks' => 'Final finance authorization'],
            ],
            'CTR/2024/005' => [
                ['user' => $admin, 'remarks' => 'Executive oversight'],
                ['user' => $kontrak, 'remarks' => 'Contract completeness validation'],
            ],
            'CTR/2024/006' => [
                ['user' => $john, 'remarks' => 'Single-step operational approval'],
            ],
            'CTR/2024/007' => [
                ['user' => $supervisor, 'remarks' => 'Execution quality check'],
                ['user' => $ken, 'remarks' => 'Payment packet verification'],
            ],
            'CTR/2024/009' => [
                ['user' => $kontrak, 'remarks' => 'Contract admin verification'],
                ['user' => $supervisor, 'remarks' => 'Operational sign-off'],
            ],
            'CTR/2024/011' => [
                ['user' => $supervisor, 'remarks' => 'Operational milestone validation'],
                ['user' => $sinta, 'remarks' => 'Procurement audit checkpoint'],
                ['user' => $manager, 'remarks' => 'Final financial approval'],
            ],
            'CTR/2024/012' => [
                ['user' => $ken, 'remarks' => 'Single approver for low-risk contract'],
            ],
        ];

        foreach ($approverConfig as $contractNumber => $approvers) {
            $contract = $contracts[$contractNumber] ?? null;
            if (!$contract) continue;

            // Masters are already seeded by ContractSeeder; find next sequence
            $nextSeq = $contract->approvers()->where('is_master', true)->count() + 1;

            foreach ($approvers as $config) {
                $user = $config['user'] ?? null;
                if (!$user) {
                    continue;
                }

                // Skip if this user is already a master approver
                $existingMaster = $contract->approvers()
                    ->where('user_id', $user->id)
                    ->where('is_master', true)
                    ->exists();

                if ($existingMaster) {
                    continue;
                }

                ContractApprover::updateOrCreate(
                    [
                        'contract_id' => $contract->id,
                        'user_id' => $user->id,
                    ],
                    [
                        'sequence_no' => $nextSeq++,
                        'remarks' => $config['remarks'] ?? null,
                        'is_master' => false,
                    ]
                );
            }
        }

        $this->command->info('  → Contract approvers configured for ' . count($approverConfig) . ' contracts');

        // ────────────────────────────────────────────────────────────────────
        //  2. Ticket Approval Steps
        //     Only non-draft tickets get steps (steps are created on submit).
        //     Draft tickets remain without steps — they haven't been submitted yet.
        // ────────────────────────────────────────────────────────────────────

        // Helper: Create fully-approved steps for a ticket
        $fullyApproved = function (string $ticketNum, array $approvers, string $approvedAt) use ($tickets) {
            $ticket = $tickets[$ticketNum] ?? null;
            if (!$ticket) return;
            foreach ($approvers as $seq => $user) {
                TicketApprovalStep::create([
                    'ticket_id'        => $ticket->id,
                    'approver_user_id' => $user->id,
                    'sequence_no'      => $seq + 1,
                    'status'           => 'approved',
                    'acted_at'         => $approvedAt,
                    'remarks'          => null,
                ]);
            }
        };

        // Helper: Create steps where first N are approved and rest are pending
        $partiallyApproved = function (string $ticketNum, array $approvers, int $approvedCount, string $actedAt) use ($tickets) {
            $ticket = $tickets[$ticketNum] ?? null;
            if (!$ticket) return;
            foreach ($approvers as $seq => $user) {
                TicketApprovalStep::create([
                    'ticket_id'        => $ticket->id,
                    'approver_user_id' => $user->id,
                    'sequence_no'      => $seq + 1,
                    'status'           => $seq < $approvedCount ? 'approved' : 'pending',
                    'acted_at'         => $seq < $approvedCount ? $actedAt : null,
                    'remarks'          => null,
                ]);
            }
        };

        // Helper: Create steps where one is rejected
        $rejected = function (string $ticketNum, array $approvers, int $rejectedAt, string $actedAt, string $remark) use ($tickets) {
            $ticket = $tickets[$ticketNum] ?? null;
            if (!$ticket) return;
            foreach ($approvers as $seq => $user) {
                $status = 'pending';
                $stepActedAt = null;
                $stepRemarks = null;

                if ($seq < $rejectedAt) {
                    $status = 'approved';
                    $stepActedAt = $actedAt;
                } elseif ($seq === $rejectedAt) {
                    $status = 'rejected';
                    $stepActedAt = $actedAt;
                    $stepRemarks = $remark;
                }

                TicketApprovalStep::create([
                    'ticket_id'        => $ticket->id,
                    'approver_user_id' => $user->id,
                    'sequence_no'      => $seq + 1,
                    'status'           => $status,
                    'acted_at'         => $stepActedAt,
                    'remarks'          => $stepRemarks,
                ]);
            }
        };

        // ── CTR/2024/001: [supervisor, manager] ──
        $fullyApproved('TKT/2024/001', [$supervisor, $manager], '2024-01-18 14:30:00');
        $partiallyApproved('TKT/2024/002', [$supervisor, $manager], 0, '2024-02-19 09:00:00');

        // ── CTR/2024/002: [kontrak, manager] ──
        $fullyApproved('TKT/2024/003', [$kontrak, $manager], '2024-02-25 16:00:00');

        // ── CTR/2024/003: [supervisor, kontrak] ──
        $rejected('TKT/2024/004', [$supervisor, $kontrak], 0, '2024-03-13 10:00:00', 'Dokumen BAST belum ditandatangani. Mohon dilengkapi terlebih dahulu.');
        // TKT/2024/005 is draft — no steps

        // ── CTR/2024/004: [supervisor, kontrak, manager] ──
        $fullyApproved('TKT/2024/006', [$supervisor, $kontrak, $manager], '2024-04-20 14:00:00');
        $fullyApproved('TKT/2024/007', [$supervisor, $kontrak, $manager], '2024-05-25 15:00:00');
        $fullyApproved('TKT/2024/008', [$supervisor, $kontrak, $manager], '2024-06-28 14:00:00');
        $partiallyApproved('TKT/2024/009', [$supervisor, $kontrak, $manager], 1, '2024-07-30 10:00:00');
        // TKT/2024/010 is draft — no steps

        // ── CTR/2024/005: [admin, kontrak] ──
        $fullyApproved('TKT/2024/011', [$admin, $kontrak], '2024-05-30 14:00:00');
        $partiallyApproved('TKT/2024/012', [$admin, $kontrak], 1, '2024-07-08 10:00:00');

        // ── CTR/2024/006: [supervisor] ──
        $fullyApproved('TKT/2024/013', [$supervisor], '2024-06-17 15:00:00');

        // ── CTR/2024/007: [supervisor, kontrak] ──
        $fullyApproved('TKT/2024/014', [$supervisor, $kontrak], '2024-07-25 14:00:00');
        $fullyApproved('TKT/2024/015', [$supervisor, $kontrak], '2024-08-15 14:00:00');
        // TKT/2024/016 is draft — no steps

        // ── CTR/2024/009: [kontrak, supervisor] ──
        $fullyApproved('TKT/2024/018', [$kontrak, $supervisor], '2024-09-20 14:00:00');
        $partiallyApproved('TKT/2024/019', [$kontrak, $supervisor], 1, '2024-11-08 10:00:00');

        // ── CTR/2024/011: [supervisor, kontrak, manager] ──
        $partiallyApproved('TKT/2024/021', [$supervisor, $kontrak, $manager], 0, '2024-11-29 08:00:00');

        // ── CTR/2024/012: [kontrak] ──
        $fullyApproved('TKT/2024/022', [$kontrak], '2024-12-15 14:00:00');

        $this->command->info('  → Approval steps created for submitted tickets');

        // ────────────────────────────────────────────────────────────────────
        //  3. Sync Payment Caches
        //     Update payment_total_paid / payment_balance on contracts
        //     that have paid tickets.
        // ────────────────────────────────────────────────────────────────────

        $contractsWithPaid = Contract::whereHas('tickets', fn ($q) => $q->where('approval_status', 'paid'))
            ->get();

        foreach ($contractsWithPaid as $contract) {
            $contract->syncPaymentCache();
        }

        $this->command->info('  → Payment cache synced for ' . $contractsWithPaid->count() . ' contracts');
    }
}
