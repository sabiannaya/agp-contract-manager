<?php

use App\Models\Contract;
use App\Models\Vendor;

beforeEach(function () {
    $this->admin = $this->createAdminUser();
});

// ─── Index ─────────────────────────────────────────────────────
test('guests cannot access contracts index', function () {
    $this->get(route('contracts.index'))->assertRedirect(route('login'));
});

test('authenticated users can view contracts index', function () {
    $this->actingAs($this->admin)
        ->get(route('contracts.index'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page->component('Contracts/Index'));
});

test('contracts index returns paginated data', function () {
    $vendor = Vendor::factory()->create();
    Contract::factory()->count(3)->for($vendor)->create();

    $this->actingAs($this->admin)
        ->get(route('contracts.index'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('Contracts/Index')
            ->has('contracts.data', 3)
        );
});

test('contracts index can filter by search', function () {
    Contract::factory()->create(['number' => 'CTR-FINDME-001']);
    Contract::factory()->create(['number' => 'CTR-OTHER-002']);

    $this->actingAs($this->admin)
        ->get(route('contracts.index', ['search' => 'FINDME']))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->has('contracts.data', 1)
            ->where('contracts.data.0.number', 'CTR-FINDME-001')
        );
});

test('contracts index can filter by cooperation type', function () {
    Contract::factory()->routine()->create();
    Contract::factory()->progress()->create();

    $this->actingAs($this->admin)
        ->get(route('contracts.index', ['cooperation_type' => 'progress']))
        ->assertOk()
        ->assertInertia(fn ($page) => $page->has('contracts.data', 1));
});

// ─── Store ─────────────────────────────────────────────────────
test('admin can create a contract', function () {
    $vendor = Vendor::factory()->create();

    $this->actingAs($this->admin)
        ->post(route('contracts.store'), [
            'number' => 'CTR-NEW-001',
            'date' => '2026-01-15',
            'vendor_id' => $vendor->id,
            'amount' => 100000000,
            'cooperation_type' => 'routine',
            'is_active' => true,
        ])
        ->assertRedirect(route('contracts.index'));

    $this->assertDatabaseHas('contracts', [
        'number' => 'CTR-NEW-001',
        'vendor_id' => $vendor->id,
        'cooperation_type' => 'routine',
    ]);
});

test('creating a progress contract requires term fields', function () {
    $vendor = Vendor::factory()->create();

    $this->actingAs($this->admin)
        ->post(route('contracts.store'), [
            'number' => 'CTR-PROG-001',
            'date' => '2026-01-15',
            'vendor_id' => $vendor->id,
            'amount' => 100000000,
            'cooperation_type' => 'progress',
            'is_active' => true,
        ])
        ->assertSessionHasErrors(['term_count', 'term_percentages']);
});

test('progress contract term percentages must sum to 100', function () {
    $vendor = Vendor::factory()->create();

    $this->actingAs($this->admin)
        ->post(route('contracts.store'), [
            'number' => 'CTR-PROG-002',
            'date' => '2026-01-15',
            'vendor_id' => $vendor->id,
            'amount' => 100000000,
            'cooperation_type' => 'progress',
            'term_count' => 2,
            'term_percentages' => [30, 40],
            'is_active' => true,
        ])
        ->assertSessionHasErrors('term_percentages');
});

test('valid progress contract can be created', function () {
    $vendor = Vendor::factory()->create();

    $this->actingAs($this->admin)
        ->post(route('contracts.store'), [
            'number' => 'CTR-PROG-003',
            'date' => '2026-01-15',
            'vendor_id' => $vendor->id,
            'amount' => 100000000,
            'cooperation_type' => 'progress',
            'term_count' => 3,
            'term_percentages' => [30, 30, 40],
            'is_active' => true,
        ])
        ->assertRedirect(route('contracts.index'));

    $this->assertDatabaseHas('contracts', [
        'number' => 'CTR-PROG-003',
        'cooperation_type' => 'progress',
        'term_count' => 3,
    ]);
});

// ─── Show ──────────────────────────────────────────────────────
test('admin can view contract details', function () {
    $contract = Contract::factory()->create();

    $this->actingAs($this->admin)
        ->get(route('contracts.show', $contract))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('Contracts/Show')
            ->has('contract')
            ->has('eligibleMasters')
        );
});

// ─── Update ────────────────────────────────────────────────────
test('admin can update a contract', function () {
    $contract = Contract::factory()->create(['amount' => 50000000]);

    $this->actingAs($this->admin)
        ->put(route('contracts.update', $contract), [
            'number' => $contract->number,
            'date' => $contract->date->format('Y-m-d'),
            'vendor_id' => $contract->vendor_id,
            'amount' => 75000000,
            'cooperation_type' => 'routine',
            'is_active' => true,
        ])
        ->assertRedirect();

    $contract->refresh();
    expect((float) $contract->amount)->toBe(75000000.00);
});

test('contract update redirects back (not to index)', function () {
    $contract = Contract::factory()->create();

    $response = $this->actingAs($this->admin)
        ->from(route('contracts.show', $contract))
        ->put(route('contracts.update', $contract), [
            'number' => $contract->number,
            'date' => $contract->date->format('Y-m-d'),
            'vendor_id' => $contract->vendor_id,
            'amount' => $contract->amount,
            'cooperation_type' => $contract->cooperation_type,
            'is_active' => true,
        ]);

    $response->assertRedirect(route('contracts.show', $contract));
});

// ─── Edit/Create pages ─────────────────────────────────────────
test('edit route returns dedicated edit page', function () {
    $contract = Contract::factory()->create();

    $this->actingAs($this->admin)
        ->get(route('contracts.edit', $contract))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('Contracts/Edit')
            ->has('contract')
            ->has('eligibleMasters')
        );
});

test('create route returns dedicated create page', function () {
    $this->actingAs($this->admin)
        ->get(route('contracts.create'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('Contracts/Create')
            ->has('eligibleMasters')
        );
});

// ─── Destroy ───────────────────────────────────────────────────
test('admin can delete a contract', function () {
    $contract = Contract::factory()->create();

    $this->actingAs($this->admin)
        ->delete(route('contracts.destroy', $contract))
        ->assertRedirect(route('contracts.index'));

    $this->assertSoftDeleted('contracts', ['id' => $contract->id]);
});

// ─── Unique number validation ──────────────────────────────────
test('contract number must be unique', function () {
    $vendor = Vendor::factory()->create();
    Contract::factory()->create(['number' => 'CTR-DUP-001']);

    $this->actingAs($this->admin)
        ->post(route('contracts.store'), [
            'number' => 'CTR-DUP-001',
            'date' => '2026-01-15',
            'vendor_id' => $vendor->id,
            'amount' => 100000000,
            'cooperation_type' => 'routine',
            'is_active' => true,
        ])
        ->assertSessionHasErrors('number');
});

// ─── Assigned Master ───────────────────────────────────────────
test('contract can be created with assigned master', function () {
    $vendor = Vendor::factory()->create();
    $master = $this->createUserWithPrivileges(['contracts' => ['read', 'update']]);

    $this->actingAs($this->admin)
        ->post(route('contracts.store'), [
            'number' => 'CTR-MASTER-001',
            'date' => '2026-01-15',
            'vendor_id' => $vendor->id,
            'amount' => 100000000,
            'cooperation_type' => 'routine',
            'is_active' => true,
            'assigned_master_user_id' => $master->id,
        ])
        ->assertRedirect(route('contracts.index'));

    $this->assertDatabaseHas('contracts', [
        'number' => 'CTR-MASTER-001',
        'assigned_master_user_id' => $master->id,
    ]);
});

// ─── Auto-seeded Approvers ─────────────────────────────────────
test('creating a contract auto-seeds creator as master approver', function () {
    $vendor = Vendor::factory()->create();

    $this->actingAs($this->admin)
        ->post(route('contracts.store'), [
            'number' => 'CTR-AUTO-001',
            'date' => '2026-01-15',
            'vendor_id' => $vendor->id,
            'amount' => 100000000,
            'cooperation_type' => 'routine',
            'is_active' => true,
        ])
        ->assertRedirect(route('contracts.index'));

    $contract = Contract::where('number', 'CTR-AUTO-001')->first();

    $this->assertDatabaseHas('contract_approvers', [
        'contract_id' => $contract->id,
        'user_id' => $this->admin->id,
        'is_master' => true,
        'sequence_no' => 1,
    ]);
});

test('creating a contract with assigned master seeds both as approvers', function () {
    $vendor = Vendor::factory()->create();
    $master = $this->createUserWithPrivileges(['contracts' => ['read', 'update']]);

    $this->actingAs($this->admin)
        ->post(route('contracts.store'), [
            'number' => 'CTR-AUTO-002',
            'date' => '2026-01-15',
            'vendor_id' => $vendor->id,
            'amount' => 100000000,
            'cooperation_type' => 'routine',
            'is_active' => true,
            'assigned_master_user_id' => $master->id,
        ])
        ->assertRedirect(route('contracts.index'));

    $contract = Contract::where('number', 'CTR-AUTO-002')->first();

    // Creator at seq 1
    $this->assertDatabaseHas('contract_approvers', [
        'contract_id' => $contract->id,
        'user_id' => $this->admin->id,
        'is_master' => true,
        'sequence_no' => 1,
    ]);

    // Assigned master at seq 2
    $this->assertDatabaseHas('contract_approvers', [
        'contract_id' => $contract->id,
        'user_id' => $master->id,
        'is_master' => true,
        'sequence_no' => 2,
    ]);
});

test('updating contract master re-syncs approvers', function () {
    $vendor = Vendor::factory()->create();
    $master1 = $this->createUserWithPrivileges(['contracts' => ['read', 'update']]);
    $master2 = $this->createUserWithPrivileges(['contracts' => ['read', 'update']]);

    // Create with master1
    $this->actingAs($this->admin)
        ->post(route('contracts.store'), [
            'number' => 'CTR-RESYNC-001',
            'date' => '2026-01-15',
            'vendor_id' => $vendor->id,
            'amount' => 100000000,
            'cooperation_type' => 'routine',
            'is_active' => true,
            'assigned_master_user_id' => $master1->id,
        ]);

    $contract = Contract::where('number', 'CTR-RESYNC-001')->first();
    expect($contract->approvers()->where('user_id', $master1->id)->where('is_master', true)->exists())->toBeTrue();

    // Update to master2
    $this->actingAs($this->admin)
        ->put(route('contracts.update', $contract), [
            'number' => $contract->number,
            'date' => $contract->date->format('Y-m-d'),
            'vendor_id' => $contract->vendor_id,
            'amount' => $contract->amount,
            'cooperation_type' => 'routine',
            'is_active' => true,
            'assigned_master_user_id' => $master2->id,
        ]);

    $contract->refresh();

    // master1 should no longer be a master approver
    expect($contract->approvers()->where('user_id', $master1->id)->where('is_master', true)->exists())->toBeFalse();
    // master2 should now be a master approver
    expect($contract->approvers()->where('user_id', $master2->id)->where('is_master', true)->exists())->toBeTrue();
});

test('sync approvers cannot remove master approvers', function () {
    $vendor = Vendor::factory()->create();
    $contract = Contract::factory()->create([
        'created_by_user_id' => $this->admin->id,
    ]);
    $contract->syncMasterApprovers();

    // Try syncing with empty approvers (only masters remain)
    $this->actingAs($this->admin)
        ->post(route('contracts.sync-approvers', $contract), [
            'approvers' => [],
        ]);

    // Master approver should still exist
    expect($contract->approvers()->where('is_master', true)->count())->toBe(1);
    expect($contract->approvers()->where('user_id', $this->admin->id)->exists())->toBeTrue();
});

// ─── Payment cache sync ────────────────────────────────────────
test('updating contract amount syncs payment cache', function () {
    $contract = Contract::factory()->create(['amount' => 100000000]);

    $this->actingAs($this->admin)
        ->put(route('contracts.update', $contract), [
            'number' => $contract->number,
            'date' => $contract->date->format('Y-m-d'),
            'vendor_id' => $contract->vendor_id,
            'amount' => 200000000,
            'cooperation_type' => 'routine',
            'is_active' => true,
        ])
        ->assertRedirect();

    $contract->refresh();
    expect((float) $contract->payment_balance)->toBe(200000000.00);
});
