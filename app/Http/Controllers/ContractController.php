<?php

namespace App\Http\Controllers;

use App\Http\Requests\ContractRequest;
use App\Models\Contract;
use App\Models\ContractApprover;
use App\Models\User;
use App\Models\Vendor;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ContractController extends Controller
{
    public function index(Request $request): Response
    {
        $user = $request->user();

        $query = Contract::query()
            ->with('vendor:id,code,name')
            ->with('approvers.user:id,name,email')
            ->withCount('tickets');

        // Non-admin users only see contracts they're stakeholders of
        if (!$user->isAdmin()) {
            $query->forStakeholder($user);
        }

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
        $eligibleMasters = User::eligibleApprovers()
            ->select('id', 'name', 'email')
            ->orderBy('name')
            ->get();

        return Inertia::render('Contracts/Create', [
            'eligibleMasters' => $eligibleMasters,
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

        $contract = Contract::create($data);

        // Auto-seed contract masters as approvers
        $contract->syncMasterApprovers();

        return redirect()
            ->route('contracts.index')
            ->with('success', 'Contract created successfully.');
    }

    public function show(Contract $contract): Response
    {
        $user = request()->user();

        // Non-admin users must be a stakeholder
        if (!$user->isAdmin() && !$this->isStakeholder($user, $contract)) {
            abort(403, 'You do not have access to this contract.');
        }

        $contract->load(['vendor', 'tickets.documents', 'createdBy', 'updatedBy', 'assignedMaster', 'approvers.user']);

        // Eligible users for contract master / approver assignment
        $eligibleMasters = User::eligibleApprovers()
            ->select('id', 'name', 'email')
            ->orderBy('name')
            ->get();

        return Inertia::render('Contracts/Show', [
            'contract' => $contract,
            'eligibleMasters' => $eligibleMasters,
        ]);
    }

    public function edit(Contract $contract): Response
    {
        $user = request()->user();

        // Non-admin users must be a stakeholder
        if (!$user->isAdmin() && !$this->isStakeholder($user, $contract)) {
            abort(403, 'You do not have access to this contract.');
        }

        $contract->load(['approvers.user']);

        $eligibleMasters = User::eligibleApprovers()
            ->select('id', 'name', 'email')
            ->orderBy('name')
            ->get();

        return Inertia::render('Contracts/Edit', [
            'contract' => $contract,
            'eligibleMasters' => $eligibleMasters,
        ]);
    }

    public function update(ContractRequest $request, Contract $contract): RedirectResponse
    {
        $user = $request->user();

        // Non-admin users must be a stakeholder
        if (!$user->isAdmin() && !$this->isStakeholder($user, $contract)) {
            abort(403, 'You do not have access to this contract.');
        }

        $data = $request->validated();
        $data['updated_by_user_id'] = $request->user()?->id;

        // Handle term percentages for routine type
        if ($data['cooperation_type'] === 'routine') {
            $data['term_count'] = null;
            $data['term_percentages'] = null;
        }

        $amountChanged = (float) $contract->amount !== (float) ($data['amount'] ?? $contract->amount);

        $contract->update($data);

        // Re-sync master approvers whenever masters may have changed
        $contract->syncMasterApprovers();

        // Resync payment cache if contract amount changed
        if ($amountChanged) {
            $contract->syncPaymentCache();
        }

        return redirect()
            ->back()
            ->with('success', 'Contract updated successfully.');
    }

    public function destroy(Contract $contract): RedirectResponse
    {
        $user = request()->user();

        // Non-admin users must be a stakeholder
        if (!$user->isAdmin() && !$this->isStakeholder($user, $contract)) {
            abort(403, 'You do not have access to this contract.');
        }

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
        $user = $request->user();

        $query = Contract::query()
            ->with('vendor:id,code,name')
            ->where('is_active', true)
            ->select('id', 'number', 'vendor_id', 'date');

        // Non-admin users only see their contracts
        if (!$user->isAdmin()) {
            $query->forStakeholder($user);
        }

        if ($vendorId = $request->get('vendor_id')) {
            $query->where('vendor_id', $vendorId);
        }

        if ($search = $request->get('search')) {
            $query->where('number', 'like', "%{$search}%");
        }

        return $query->orderBy('number')->limit(50)->get()->toArray();
    }

    /**
     * Sync the list of approvers for a contract.
     * Only contract masters can do this.
     */
    public function syncApprovers(Request $request, Contract $contract): RedirectResponse
    {
        $user = $request->user();

        if (!$contract->isContractMaster($user)) {
            return redirect()
                ->back()
                ->with('error', 'Only contract masters can manage approvers.');
        }

        $validated = $request->validate([
            'approvers' => ['present', 'array'],
            'approvers.*.user_id' => ['required', 'integer', 'exists:users,id', 'distinct'],
            'approvers.*.sequence_no' => ['required', 'integer', 'min:1'],
            'approvers.*.remarks' => ['required', 'string', 'max:1000'],
        ]);

        // Validate all approvers are privilege-eligible
        $userIds = collect($validated['approvers'])->pluck('user_id');
        $eligibleCount = User::eligibleApprovers()
            ->whereIn('id', $userIds)
            ->count();

        if ($eligibleCount !== $userIds->count()) {
            return redirect()
                ->back()
                ->with('error', 'All approvers must have contract update privileges.');
        }

        // Replace only non-master approvers; masters are managed by the system
        $contract->approvers()->where('is_master', false)->delete();

        // Determine the next sequence number after existing masters
        $masterCount = $contract->approvers()->where('is_master', true)->count();
        $seq = $masterCount + 1;

        foreach ($validated['approvers'] as $approverData) {
            // Skip if this user is already a master approver
            $isMaster = $contract->approvers()
                ->where('user_id', $approverData['user_id'])
                ->where('is_master', true)
                ->exists();

            if ($isMaster) {
                continue;
            }

            ContractApprover::create([
                'contract_id' => $contract->id,
                'user_id' => $approverData['user_id'],
                'sequence_no' => $seq++,
                'remarks' => $approverData['remarks'],
                'is_master' => false,
            ]);
        }

        return redirect()
            ->back()
            ->with('success', 'Contract approvers updated successfully.');
    }

    /**
     * Check if a user is a stakeholder for a contract.
     * Stakeholder = creator, assigned master, or configured approver.
     */
    private function isStakeholder(User $user, Contract $contract): bool
    {
        return $contract->created_by_user_id === $user->id
            || $contract->assigned_master_user_id === $user->id
            || $contract->approvers()->where('user_id', $user->id)->exists();
    }
}
