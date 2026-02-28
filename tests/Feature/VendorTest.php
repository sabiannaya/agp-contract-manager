<?php

use App\Models\Vendor;

beforeEach(function () {
    $this->admin = $this->createAdminUser();
});

// ─── Index ─────────────────────────────────────────────────────
test('guests cannot access vendors index', function () {
    $this->get(route('vendors.index'))->assertRedirect(route('login'));
});

test('authenticated users can view vendors index', function () {
    $this->actingAs($this->admin)
        ->get(route('vendors.index'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page->component('Vendors/Index'));
});

test('vendors index returns paginated data', function () {
    Vendor::factory()->count(3)->create();

    $this->actingAs($this->admin)
        ->get(route('vendors.index'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('Vendors/Index')
            ->has('vendors.data', 3)
        );
});

test('vendors index can filter by search', function () {
    Vendor::factory()->create(['name' => 'Acme Corp']);
    Vendor::factory()->create(['name' => 'Beta Inc']);

    $this->actingAs($this->admin)
        ->get(route('vendors.index', ['search' => 'Acme']))
        ->assertOk()
        ->assertInertia(fn ($page) => $page->has('vendors.data', 1));
});

// ─── Store ─────────────────────────────────────────────────────
test('admin can create a vendor', function () {
    $this->actingAs($this->admin)
        ->post(route('vendors.store'), [
            'code' => 'VND-TEST',
            'name' => 'Test Vendor',
            'address' => '123 Test Street',
            'join_date' => '2026-01-01',
            'contact_person' => 'John Doe',
            'tax_id' => '12.345.678.9-012.345',
            'is_active' => true,
        ])
        ->assertRedirect(route('vendors.index'));

    $this->assertDatabaseHas('vendors', ['code' => 'VND-TEST', 'name' => 'Test Vendor']);
});

test('vendor code must be unique', function () {
    Vendor::factory()->create(['code' => 'VND-DUP']);

    $this->actingAs($this->admin)
        ->post(route('vendors.store'), [
            'code' => 'VND-DUP',
            'name' => 'Another Vendor',
            'address' => '456 Other Street',
            'join_date' => '2026-01-01',
            'contact_person' => 'Jane Doe',
            'tax_id' => '99.999.999.9-999.999',
            'is_active' => true,
        ])
        ->assertSessionHasErrors('code');
});

// ─── Show ──────────────────────────────────────────────────────
test('admin can view vendor details', function () {
    $vendor = Vendor::factory()->create();

    $this->actingAs($this->admin)
        ->get(route('vendors.show', $vendor))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('Vendors/Show')
            ->has('vendor')
        );
});

// ─── Update ────────────────────────────────────────────────────
test('admin can update a vendor', function () {
    $vendor = Vendor::factory()->create(['name' => 'Old Name']);

    $this->actingAs($this->admin)
        ->put(route('vendors.update', $vendor), [
            'code' => $vendor->code,
            'name' => 'New Name',
            'address' => $vendor->address,
            'join_date' => $vendor->join_date->format('Y-m-d'),
            'contact_person' => $vendor->contact_person,
            'tax_id' => $vendor->tax_id,
            'is_active' => true,
        ])
        ->assertRedirect();

    $vendor->refresh();
    expect($vendor->name)->toBe('New Name');
});

test('vendor update redirects back (not to index)', function () {
    $vendor = Vendor::factory()->create();

    $response = $this->actingAs($this->admin)
        ->from(route('vendors.show', $vendor))
        ->put(route('vendors.update', $vendor), [
            'code' => $vendor->code,
            'name' => $vendor->name,
            'address' => $vendor->address,
            'join_date' => $vendor->join_date->format('Y-m-d'),
            'contact_person' => $vendor->contact_person,
            'tax_id' => $vendor->tax_id,
            'is_active' => true,
        ]);

    $response->assertRedirect(route('vendors.show', $vendor));
});

// ─── Destroy ───────────────────────────────────────────────────
test('admin can delete a vendor', function () {
    $vendor = Vendor::factory()->create();

    $this->actingAs($this->admin)
        ->delete(route('vendors.destroy', $vendor))
        ->assertRedirect(route('vendors.index'));

    $this->assertSoftDeleted('vendors', ['id' => $vendor->id]);
});
