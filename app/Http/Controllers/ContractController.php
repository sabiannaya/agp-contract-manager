<?php

namespace App\Http\Controllers;

use App\Http\Requests\ContractRequest;
use App\Models\Contract;
use App\Models\Vendor;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ContractController extends Controller
{
    public function index(Request $request): Response
    {
        $query = Contract::query()
            ->with('vendor:id,code,name')
            ->withCount('tickets');

        // Search functionality
        if ($search = $request->get('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('number', 'like', "%{$search}%")
                    ->orWhereHas('vendor', function ($q) use ($search) {
                        $q->where('name', 'like', "%{$search}%")
                            ->orWhere('code', 'like', "%{$search}%");
                    });
            });
        }

        // Filter by vendor
        if ($vendorId = $request->get('vendor_id')) {
            $query->where('vendor_id', $vendorId);
        }

        // Filter by cooperation type
        if ($type = $request->get('cooperation_type')) {
            $query->where('cooperation_type', $type);
        }

        // Filter by date range
        if ($dateFrom = $request->get('date_from')) {
            $query->whereDate('date', '>=', $dateFrom);
        }
        if ($dateTo = $request->get('date_to')) {
            $query->whereDate('date', '<=', $dateTo);
        }

        // Filter by active status
        if ($request->has('is_active')) {
            $query->where('is_active', $request->boolean('is_active'));
        }

        // Sorting
        $sortField = $request->get('sort', 'created_at');
        $sortDirection = $request->get('direction', 'desc');
        $query->orderBy($sortField, $sortDirection);

        $contracts = $query->paginate(15)->withQueryString();

        return Inertia::render('Contracts/Index', [
            'contracts' => $contracts,
            'filters' => $request->only([
                'search', 'vendor_id', 'cooperation_type',
                'date_from', 'date_to', 'is_active', 'sort', 'direction'
            ]),
        ]);
    }

    public function create(): Response
    {
        $vendors = Vendor::where('is_active', true)
            ->select('id', 'code', 'name')
            ->orderBy('name')
            ->get();

        return Inertia::render('Contracts/Create', [
            'vendors' => $vendors,
        ]);
    }

    public function store(ContractRequest $request): RedirectResponse
    {
        $data = $request->validated();
        $data['created_by_user_id'] = $request->user()?->id;

        // Handle term percentages for routine type
        if ($data['cooperation_type'] === 'routine') {
            $data['term_count'] = null;
            $data['term_percentages'] = null;
        }

        Contract::create($data);

        return redirect()
            ->route('contracts.index')
            ->with('success', 'Contract created successfully.');
    }

    public function show(Contract $contract): Response
    {
        $contract->load(['vendor', 'tickets.documents', 'createdBy', 'updatedBy']);

        return Inertia::render('Contracts/Show', [
            'contract' => $contract,
        ]);
    }

    public function edit(Contract $contract): Response
    {
        $vendors = Vendor::where('is_active', true)
            ->select('id', 'code', 'name')
            ->orderBy('name')
            ->get();

        return Inertia::render('Contracts/Edit', [
            'contract' => $contract,
            'vendors' => $vendors,
        ]);
    }

    public function update(ContractRequest $request, Contract $contract): RedirectResponse
    {
        $data = $request->validated();
        $data['updated_by_user_id'] = $request->user()?->id;

        // Handle term percentages for routine type
        if ($data['cooperation_type'] === 'routine') {
            $data['term_count'] = null;
            $data['term_percentages'] = null;
        }

        $contract->update($data);

        return redirect()
            ->route('contracts.index')
            ->with('success', 'Contract updated successfully.');
    }

    public function destroy(Contract $contract): RedirectResponse
    {
        $contract->delete();

        return redirect()
            ->route('contracts.index')
            ->with('success', 'Contract deleted successfully.');
    }

    /**
     * API endpoint for select dropdown.
     */
    public function list(Request $request): array
    {
        $query = Contract::query()
            ->with('vendor:id,code,name')
            ->where('is_active', true)
            ->select('id', 'number', 'vendor_id', 'date');

        if ($vendorId = $request->get('vendor_id')) {
            $query->where('vendor_id', $vendorId);
        }

        if ($search = $request->get('search')) {
            $query->where('number', 'like', "%{$search}%");
        }

        return $query->orderBy('number')->limit(50)->get()->toArray();
    }
}
