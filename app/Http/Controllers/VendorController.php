<?php

namespace App\Http\Controllers;

use App\Http\Requests\VendorRequest;
use App\Models\Vendor;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class VendorController extends Controller
{
    public function index(Request $request): Response
    {
        $query = Vendor::query()
            ->withCount('contracts', 'tickets');

        // Search functionality
        if ($search = $request->get('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('code', 'like', "%{$search}%")
                    ->orWhere('name', 'like', "%{$search}%")
                    ->orWhere('tax_id', 'like', "%{$search}%");
            });
        }

        // Filter by active status
        if ($request->has('is_active')) {
            $query->where('is_active', $request->boolean('is_active'));
        }

        // Sorting
        $sortField = $request->get('sort', 'created_at');
        $sortDirection = $request->get('direction', 'desc');
        $query->orderBy($sortField, $sortDirection);

        $vendors = $query->paginate(15)->withQueryString();

        return Inertia::render('Vendors/Index', [
            'vendors' => $vendors,
            'filters' => $request->only(['search', 'is_active', 'sort', 'direction']),
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('Vendors/Create');
    }

    public function store(VendorRequest $request): RedirectResponse
    {
        $data = $request->validated();
        $data['created_by_user_id'] = $request->user()?->id;

        Vendor::create($data);

        return redirect()
            ->route('vendors.index')
            ->with('success', 'Vendor created successfully.');
    }

    public function show(Vendor $vendor): Response
    {
        $vendor->load(['contracts', 'tickets', 'createdBy', 'updatedBy']);

        return Inertia::render('Vendors/Show', [
            'vendor' => $vendor,
        ]);
    }

    public function edit(Vendor $vendor): Response
    {
        return Inertia::render('Vendors/Edit', [
            'vendor' => $vendor,
        ]);
    }

    public function update(VendorRequest $request, Vendor $vendor): RedirectResponse
    {
        $data = $request->validated();
        $data['updated_by_user_id'] = $request->user()?->id;

        $vendor->update($data);

        return redirect()
            ->back()
            ->with('success', 'Vendor updated successfully.');
    }

    public function destroy(Vendor $vendor): RedirectResponse
    {
        $vendor->delete();

        return redirect()
            ->route('vendors.index')
            ->with('success', 'Vendor deleted successfully.');
    }

    /**
     * API endpoint for select dropdown.
     */
    public function list(Request $request): array
    {
        $query = Vendor::query()
            ->where('is_active', true)
            ->select('id', 'code', 'name');

        if ($search = $request->get('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('code', 'like', "%{$search}%")
                    ->orWhere('name', 'like', "%{$search}%");
            });
        }

        return $query->orderBy('name')->limit(50)->get()->toArray();
    }
}
