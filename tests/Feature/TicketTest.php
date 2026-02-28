<?php

use App\Models\Contract;
use App\Models\Ticket;
use App\Models\Vendor;

beforeEach(function () {
    $this->admin = $this->createAdminUser();
    $this->vendor = Vendor::factory()->create();
    $this->contract = Contract::factory()->for($this->vendor)->create();
});

// ─── Index ─────────────────────────────────────────────────────
test('guests cannot access tickets index', function () {
    $this->get(route('tickets.index'))->assertRedirect(route('login'));
});

test('authenticated users can view tickets index', function () {
    $this->actingAs($this->admin)
        ->get(route('tickets.index'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page->component('Tickets/Index'));
});

test('tickets index returns paginated data', function () {
    Ticket::factory()->count(3)->forContract($this->contract)->create();

    $this->actingAs($this->admin)
        ->get(route('tickets.index'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->has('tickets.data', 3)
        );
});

// ─── Store ─────────────────────────────────────────────────────
test('admin can create a ticket', function () {
    $this->actingAs($this->admin)
        ->post(route('tickets.store'), [
            'date' => '2026-02-15',
            'contract_id' => $this->contract->id,
            'vendor_id' => $this->vendor->id,
            'notes' => 'Test ticket',
            'is_active' => true,
        ])
        ->assertRedirect();

    $this->assertDatabaseHas('tickets', [
        'contract_id' => $this->contract->id,
        'vendor_id' => $this->vendor->id,
    ]);
});

test('ticket can be created with payment amount', function () {
    $this->actingAs($this->admin)
        ->post(route('tickets.store'), [
            'date' => '2026-02-15',
            'contract_id' => $this->contract->id,
            'vendor_id' => $this->vendor->id,
            'amount' => 5000000,
            'notes' => 'Payment ticket',
            'is_active' => true,
        ])
        ->assertRedirect();

    $ticket = Ticket::latest()->first();
    expect((float) $ticket->amount)->toBe(5000000.00);
    expect($ticket->approval_status)->toBe('draft');
});

// ─── Show ──────────────────────────────────────────────────────
test('admin can view ticket details', function () {
    $ticket = Ticket::factory()->forContract($this->contract)->create();

    $this->actingAs($this->admin)
        ->get(route('tickets.show', $ticket))
        ->assertOk()
        ->assertInertia(fn ($page) => $page->component('Tickets/Show'));
});

// ─── Update ────────────────────────────────────────────────────
test('admin can update a ticket', function () {
    $ticket = Ticket::factory()->forContract($this->contract)->create(['notes' => 'Old']);

    $this->actingAs($this->admin)
        ->put(route('tickets.update', $ticket), [
            'date' => $ticket->date->format('Y-m-d'),
            'contract_id' => $ticket->contract_id,
            'vendor_id' => $ticket->vendor_id,
            'notes' => 'Updated notes',
            'is_active' => true,
        ])
        ->assertRedirect();

    $ticket->refresh();
    expect($ticket->notes)->toBe('Updated notes');
});

test('ticket update redirects back (not to index)', function () {
    $ticket = Ticket::factory()->forContract($this->contract)->create();

    $response = $this->actingAs($this->admin)
        ->from(route('tickets.edit', $ticket))
        ->put(route('tickets.update', $ticket), [
            'date' => $ticket->date->format('Y-m-d'),
            'contract_id' => $ticket->contract_id,
            'vendor_id' => $ticket->vendor_id,
            'notes' => $ticket->notes ?? '',
            'is_active' => true,
        ]);

    $response->assertRedirect(route('tickets.edit', $ticket));
});

// ─── Destroy ───────────────────────────────────────────────────
test('admin can delete a ticket', function () {
    $ticket = Ticket::factory()->forContract($this->contract)->create();

    $this->actingAs($this->admin)
        ->delete(route('tickets.destroy', $ticket))
        ->assertRedirect(route('tickets.index'));

    $this->assertSoftDeleted('tickets', ['id' => $ticket->id]);
});

// ─── Number auto-generation ────────────────────────────────────
test('ticket number is auto-generated', function () {
    $this->actingAs($this->admin)
        ->post(route('tickets.store'), [
            'date' => '2026-02-15',
            'contract_id' => $this->contract->id,
            'vendor_id' => $this->vendor->id,
            'notes' => '',
            'is_active' => true,
        ]);

    $ticket = Ticket::latest()->first();
    expect($ticket->number)->not->toBeEmpty();
});
