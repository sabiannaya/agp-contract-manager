<?php

use App\Models\Contract;
use App\Models\ContractApprover;
use App\Models\Ticket;
use App\Models\TicketApprovalStep;
use App\Models\User;
use App\Models\Vendor;

beforeEach(function () {
    $this->admin = $this->createAdminUser();
    $this->vendor = Vendor::factory()->create();
    $this->contract = Contract::factory()->for($this->vendor)->create([
        'amount' => 100000000,
        'created_by_user_id' => $this->admin->id,
    ]);
});

// ─── Index ─────────────────────────────────────────────────────
test('guests cannot access payment tracker', function () {
    $this->get(route('payment-tracker.index'))->assertRedirect(route('login'));
});

test('authenticated users can view payment tracker index', function () {
    $this->actingAs($this->admin)
        ->get(route('payment-tracker.index'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page->component('PaymentTracker/Index'));
});

test('payment tracker index filters by approval status', function () {
    Ticket::factory()->forContract($this->contract)->withAmount(1000000)->draft()->create();
    Ticket::factory()->forContract($this->contract)->withAmount(2000000)->pending()->create();
    Ticket::factory()->forContract($this->contract)->withAmount(3000000)->approved()->create();

    $this->actingAs($this->admin)
        ->get(route('payment-tracker.index', ['approval_status' => 'draft']))
        ->assertOk()
        ->assertInertia(fn ($page) => $page->has('tickets.data', 1));

    $this->actingAs($this->admin)
        ->get(route('payment-tracker.index', ['approval_status' => 'pending']))
        ->assertOk()
        ->assertInertia(fn ($page) => $page->has('tickets.data', 1));
});

// ─── Show ──────────────────────────────────────────────────────
test('can view payment tracker detail', function () {
    $ticket = Ticket::factory()->forContract($this->contract)->withAmount(5000000)->create();

    $this->actingAs($this->admin)
        ->get(route('payment-tracker.show', $ticket))
        ->assertOk()
        ->assertInertia(fn ($page) => $page->component('PaymentTracker/Show'));
});

// ─── Submit for Approval ───────────────────────────────────────
test('draft ticket can be submitted for approval', function () {
    // Set up approvers for the contract
    $approver1 = $this->createUserWithPrivileges(['contracts' => ['read', 'update']]);
    $approver2 = $this->createUserWithPrivileges(['contracts' => ['read', 'update']]);

    ContractApprover::create(['contract_id' => $this->contract->id, 'user_id' => $approver1->id, 'sequence_no' => 1]);
    ContractApprover::create(['contract_id' => $this->contract->id, 'user_id' => $approver2->id, 'sequence_no' => 2]);

    $ticket = Ticket::factory()->forContract($this->contract)->withAmount(5000000)->draft()->create();

    $this->actingAs($this->admin)
        ->post(route('payment-tracker.submit', $ticket))
        ->assertRedirect();

    $ticket->refresh();
    expect($ticket->approval_status)->toBe('pending');
    expect($ticket->submitted_at)->not->toBeNull();
    expect($ticket->approvalSteps)->toHaveCount(2);
    expect($ticket->approvalSteps[0]->sequence_no)->toBe(1);
    expect($ticket->approvalSteps[0]->status)->toBe('pending');
    expect($ticket->approvalSteps[1]->sequence_no)->toBe(2);
    expect($ticket->approvalSteps[1]->status)->toBe('pending');
});

test('cannot submit ticket without amount', function () {
    $ticket = Ticket::factory()->forContract($this->contract)->draft()->create(['amount' => null]);

    $this->actingAs($this->admin)
        ->post(route('payment-tracker.submit', $ticket))
        ->assertRedirect()
        ->assertSessionHas('error');
});

test('cannot submit ticket exceeding contract balance', function () {
    // Contract amount is 100M, try to submit for 200M
    $ticket = Ticket::factory()->forContract($this->contract)->draft()->create(['amount' => 200000000]);

    ContractApprover::create(['contract_id' => $this->contract->id, 'user_id' => $this->admin->id, 'sequence_no' => 1]);

    $this->actingAs($this->admin)
        ->post(route('payment-tracker.submit', $ticket))
        ->assertRedirect()
        ->assertSessionHas('error');
});

// ─── Approve ───────────────────────────────────────────────────
test('approver can approve their pending step', function () {
    $approver = $this->createUserWithPrivileges(['contracts' => ['read', 'update'], 'payment_tracker' => ['read', 'update']]);

    ContractApprover::create(['contract_id' => $this->contract->id, 'user_id' => $approver->id, 'sequence_no' => 1]);

    $ticket = Ticket::factory()->forContract($this->contract)->withAmount(5000000)->pending()->create();

    TicketApprovalStep::create([
        'ticket_id' => $ticket->id,
        'approver_user_id' => $approver->id,
        'sequence_no' => 1,
        'status' => 'pending',
    ]);

    $this->actingAs($approver)
        ->post(route('payment-tracker.approve', $ticket), ['remarks' => 'Looks good'])
        ->assertRedirect();

    $ticket->refresh();
    $step = $ticket->approvalSteps()->first();
    expect($step->status)->toBe('approved');
    expect($step->remarks)->toBe('Looks good');
    expect($step->acted_at)->not->toBeNull();
});

test('fully approved ticket transitions to approved status', function () {
    $approver = $this->createUserWithPrivileges(['contracts' => ['read', 'update'], 'payment_tracker' => ['read', 'update']]);

    ContractApprover::create(['contract_id' => $this->contract->id, 'user_id' => $approver->id, 'sequence_no' => 1]);

    $ticket = Ticket::factory()->forContract($this->contract)->withAmount(5000000)->pending()->create();

    TicketApprovalStep::create([
        'ticket_id' => $ticket->id,
        'approver_user_id' => $approver->id,
        'sequence_no' => 1,
        'status' => 'pending',
    ]);

    $this->actingAs($approver)
        ->post(route('payment-tracker.approve', $ticket))
        ->assertRedirect();

    $ticket->refresh();
    expect($ticket->approval_status)->toBe('approved');
    expect($ticket->approved_at)->not->toBeNull();
});

// ─── Reject ────────────────────────────────────────────────────
test('approver can reject with remarks', function () {
    $approver = $this->createUserWithPrivileges(['contracts' => ['read', 'update'], 'payment_tracker' => ['read', 'update']]);

    // Make user a contract approver so they pass stakeholder gate
    ContractApprover::create(['contract_id' => $this->contract->id, 'user_id' => $approver->id, 'sequence_no' => 1]);

    $ticket = Ticket::factory()->forContract($this->contract)->withAmount(5000000)->pending()->create();

    TicketApprovalStep::create([
        'ticket_id' => $ticket->id,
        'approver_user_id' => $approver->id,
        'sequence_no' => 1,
        'status' => 'pending',
    ]);

    $this->actingAs($approver)
        ->post(route('payment-tracker.reject', $ticket), ['remarks' => 'Incomplete documentation'])
        ->assertRedirect();

    $ticket->refresh();
    expect($ticket->approval_status)->toBe('rejected');

    $step = $ticket->approvalSteps()->first();
    expect($step->status)->toBe('rejected');
    expect($step->remarks)->toBe('Incomplete documentation');
});

test('reject requires remarks', function () {
    $approver = $this->createUserWithPrivileges(['contracts' => ['read', 'update'], 'payment_tracker' => ['read', 'update']]);

    // Make user a contract approver so they pass stakeholder gate
    ContractApprover::create(['contract_id' => $this->contract->id, 'user_id' => $approver->id, 'sequence_no' => 1]);

    $ticket = Ticket::factory()->forContract($this->contract)->withAmount(5000000)->pending()->create();

    TicketApprovalStep::create([
        'ticket_id' => $ticket->id,
        'approver_user_id' => $approver->id,
        'sequence_no' => 1,
        'status' => 'pending',
    ]);

    $this->actingAs($approver)
        ->post(route('payment-tracker.reject', $ticket), ['remarks' => ''])
        ->assertSessionHasErrors('remarks');
});

// ─── Mark Paid ─────────────────────────────────────────────────
test('contract master can mark approved ticket as paid', function () {
    $ticket = Ticket::factory()->forContract($this->contract)->withAmount(5000000)->approved()->create();

    $this->actingAs($this->admin) // admin is contract creator = master
        ->post(route('payment-tracker.mark-paid', $ticket), ['reference_no' => 'TF-123456'])
        ->assertRedirect();

    $ticket->refresh();
    expect($ticket->approval_status)->toBe('paid');
    expect($ticket->paid_at)->not->toBeNull();
    expect($ticket->reference_no)->toBe('TF-123456');

    // Check contract payment cache is updated
    $this->contract->refresh();
    expect((float) $this->contract->payment_total_paid)->toBe(5000000.00);
});

test('non-master cannot mark ticket as paid', function () {
    $otherUser = $this->createUserWithPrivileges(['contracts' => ['read'], 'payment_tracker' => ['read', 'update']]);

    // Make user a contract approver so they pass stakeholder gate, but not a master
    ContractApprover::create(['contract_id' => $this->contract->id, 'user_id' => $otherUser->id, 'sequence_no' => 1]);

    $ticket = Ticket::factory()->forContract($this->contract)->withAmount(5000000)->approved()->create();

    $this->actingAs($otherUser)
        ->post(route('payment-tracker.mark-paid', $ticket), ['reference_no' => 'TF-000'])
        ->assertRedirect()
        ->assertSessionHas('error');
});

test('cannot mark non-approved ticket as paid', function () {
    $ticket = Ticket::factory()->forContract($this->contract)->withAmount(5000000)->pending()->create();

    $this->actingAs($this->admin)
        ->post(route('payment-tracker.mark-paid', $ticket), ['reference_no' => 'TF-000'])
        ->assertRedirect()
        ->assertSessionHas('error');
});

// ─── Contract Approvers Sync ───────────────────────────────────
test('contract master can sync approvers', function () {
    $approver1 = $this->createUserWithPrivileges(['contracts' => ['read', 'update']]);
    $approver2 = $this->createUserWithPrivileges(['contracts' => ['read', 'update']]);

    $this->actingAs($this->admin)
        ->post(route('contracts.sync-approvers', $this->contract), [
            'approvers' => [
                ['user_id' => $approver1->id, 'sequence_no' => 1, 'remarks' => 'Primary approver for compliance'],
                ['user_id' => $approver2->id, 'sequence_no' => 2, 'remarks' => 'Secondary approver for finance control'],
            ],
        ])
        ->assertRedirect();

    expect($this->contract->approvers()->count())->toBe(2);
});

test('non-master cannot sync approvers', function () {
    $otherUser = $this->createUserWithPrivileges(['contracts' => ['read']]);
    $approver = $this->createUserWithPrivileges(['contracts' => ['read', 'update']]);

    $this->actingAs($otherUser)
        ->post(route('contracts.sync-approvers', $this->contract), [
            'approvers' => [
                ['user_id' => $approver->id, 'sequence_no' => 1, 'remarks' => 'Unauthorized user attempt'],
            ],
        ])
        ->assertRedirect()
        ->assertSessionHas('error');
});

// ─── Sequential Approval ───────────────────────────────────────
test('second approver cannot approve before first', function () {
    $approver1 = $this->createUserWithPrivileges(['contracts' => ['read', 'update'], 'payment_tracker' => ['read', 'update']]);
    $approver2 = $this->createUserWithPrivileges(['contracts' => ['read', 'update'], 'payment_tracker' => ['read', 'update']]);

    // Make users contract approvers so they pass stakeholder gate
    ContractApprover::create(['contract_id' => $this->contract->id, 'user_id' => $approver1->id, 'sequence_no' => 1]);
    ContractApprover::create(['contract_id' => $this->contract->id, 'user_id' => $approver2->id, 'sequence_no' => 2]);

    $ticket = Ticket::factory()->forContract($this->contract)->withAmount(5000000)->pending()->create();

    TicketApprovalStep::create([
        'ticket_id' => $ticket->id,
        'approver_user_id' => $approver1->id,
        'sequence_no' => 1,
        'status' => 'pending',
    ]);
    TicketApprovalStep::create([
        'ticket_id' => $ticket->id,
        'approver_user_id' => $approver2->id,
        'sequence_no' => 2,
        'status' => 'pending',
    ]);

    // Approver 2 tries to approve before Approver 1
    $this->actingAs($approver2)
        ->post(route('payment-tracker.approve', $ticket))
        ->assertRedirect()
        ->assertSessionHas('error');
});

// ─── Payment Balance Tracking ──────────────────────────────────
test('contract balance decreases after payment is marked paid', function () {
    // Create and mark ticket as paid
    $ticket = Ticket::factory()->forContract($this->contract)->create([
        'amount' => 30000000,
        'approval_status' => 'approved',
        'submitted_at' => now()->subDay(),
        'approved_at' => now(),
    ]);

    $this->actingAs($this->admin)
        ->post(route('payment-tracker.mark-paid', $ticket), ['reference_no' => 'TF-1'])
        ->assertRedirect();

    $this->contract->refresh();
    expect((float) $this->contract->payment_total_paid)->toBe(30000000.00);
    expect((float) $this->contract->payment_balance)->toBe(70000000.00);
});
