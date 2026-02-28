<?php

namespace App\Http\Controllers;

use App\Http\Requests\TicketRequest;
use App\Models\Contract;
use App\Models\Ticket;
use App\Models\Vendor;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class TicketController extends Controller
{
    public function index(Request $request): Response
    {
        $user = $request->user();

        $query = Ticket::query()
            ->with(['vendor:id,code,name', 'contract:id,number'])
            ->withCount('documents');

        // Non-admin users only see tickets for their contracts
        if (!$user->isAdmin()) {
            $contractIds = $user->stakeholderContractIds();
            $query->whereIn('contract_id', $contractIds);
        }

        // Search functionality
        if ($search = $request->get('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('number', 'like', "%{$search}%")
                    ->orWhereHas('vendor', function ($q) use ($search) {
                        $q->where('name', 'like', "%{$search}%")
                            ->orWhere('code', 'like', "%{$search}%");
                    })
                    ->orWhereHas('contract', function ($q) use ($search) {
                        $q->where('number', 'like', "%{$search}%");
                    });
            });
        }

        // Filter by vendor
        if ($vendorId = $request->get('vendor_id')) {
            $query->where('vendor_id', $vendorId);
        }

        // Filter by contract
        if ($contractId = $request->get('contract_id')) {
            $query->where('contract_id', $contractId);
        }

        // Filter by status
        if ($status = $request->get('status')) {
            $query->where('status', $status);
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

        $tickets = $query->paginate(15)->withQueryString();

        return Inertia::render('Tickets/Index', [
            'tickets' => $tickets,
            'filters' => $request->only([
                'search', 'vendor_id', 'contract_id', 'status',
                'date_from', 'date_to', 'is_active', 'sort', 'direction'
            ]),
        ]);
    }

    public function create(): Response
    {
        $user = request()->user();

        $vendors = Vendor::where('is_active', true)
            ->select('id', 'code', 'name')
            ->orderBy('name')
            ->get();

        $contractQuery = Contract::where('is_active', true)
            ->with('vendor:id,code,name')
            ->select('id', 'number', 'vendor_id', 'amount', 'cooperation_type', 'payment_total_paid', 'payment_balance');

        // Non-admin users only see their contracts
        if (!$user->isAdmin()) {
            $contractQuery->forStakeholder($user);
        }

        $contracts = $contractQuery->orderBy('number')->get();

        return Inertia::render('Tickets/Create', [
            'vendors' => $vendors,
            'contracts' => $contracts,
        ]);
    }

    public function store(TicketRequest $request): RedirectResponse
    {
        $data = $request->validated();
        $data['number'] = Ticket::generateNumber();
        $data['created_by_user_id'] = $request->user()?->id;
        $data['status'] = 'incomplete';

        $ticket = Ticket::create($data);

        return redirect()
            ->route('tickets.edit', $ticket)
            ->with('success', 'Ticket created successfully. Now upload the required documents.');
    }

    public function show(Ticket $ticket): Response
    {
        $user = request()->user();

        // Non-admin users must be a stakeholder of the ticket's contract
        if (!$user->isAdmin()) {
            $contractIds = $user->stakeholderContractIds();
            if (!in_array($ticket->contract_id, $contractIds)) {
                abort(403, 'You do not have access to this ticket.');
            }
        }

        $ticket->load([
            'vendor',
            'contract',
            'documents',
            'createdBy',
            'updatedBy',
        ]);

        return Inertia::render('Tickets/Show', [
            'ticket' => $ticket,
        ]);
    }

    public function edit(Ticket $ticket): Response
    {
        $user = request()->user();

        // Non-admin users must be a stakeholder of the ticket's contract
        if (!$user->isAdmin()) {
            $contractIds = $user->stakeholderContractIds();
            if (!in_array($ticket->contract_id, $contractIds)) {
                abort(403, 'You do not have access to this ticket.');
            }
        }

        $ticket->load(['documents']);

        $vendors = Vendor::where('is_active', true)
            ->select('id', 'code', 'name')
            ->orderBy('name')
            ->get();

        $contractQuery = Contract::where('is_active', true)
            ->with('vendor:id,code,name')
            ->select('id', 'number', 'vendor_id', 'amount', 'cooperation_type', 'payment_total_paid', 'payment_balance');

        // Non-admin users only see their contracts
        if (!$user->isAdmin()) {
            $contractQuery->forStakeholder($user);
        }

        $contracts = $contractQuery->orderBy('number')->get();

        return Inertia::render('Tickets/Edit', [
            'ticket' => $ticket,
            'vendors' => $vendors,
            'contracts' => $contracts,
        ]);
    }

    public function update(TicketRequest $request, Ticket $ticket): RedirectResponse
    {
        $user = $request->user();

        // Non-admin users must be a stakeholder of the ticket's contract
        if (!$user->isAdmin()) {
            $contractIds = $user->stakeholderContractIds();
            if (!in_array($ticket->contract_id, $contractIds)) {
                abort(403, 'You do not have access to this ticket.');
            }
        }

        $data = $request->validated();
        $data['updated_by_user_id'] = $request->user()?->id;

        // Remove number from update to prevent modification
        unset($data['number']);

        $ticket->update($data);

        return redirect()
            ->back()
            ->with('success', 'Ticket updated successfully.');
    }

    public function destroy(Ticket $ticket): RedirectResponse
    {
        $user = request()->user();

        // Non-admin users must be a stakeholder of the ticket's contract
        if (!$user->isAdmin()) {
            $contractIds = $user->stakeholderContractIds();
            if (!in_array($ticket->contract_id, $contractIds)) {
                abort(403, 'You do not have access to this ticket.');
            }
        }

        $ticket->delete();

        return redirect()
            ->route('tickets.index')
            ->with('success', 'Ticket deleted successfully.');
    }

    /**
     * View page with advanced filters.
     */
    public function view(Request $request): Response
    {
        $user = $request->user();

        $query = Ticket::query()
            ->with([
                'vendor:id,code,name',
                'contract:id,number',
                'documents:id,ticket_id,type,original_name',
            ])
            ->withCount('documents');

        // Non-admin users only see tickets for their contracts
        if (!$user->isAdmin()) {
            $contractIds = $user->stakeholderContractIds();
            $query->whereIn('contract_id', $contractIds);
        }

        // Search functionality
        if ($search = $request->get('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('number', 'like', "%{$search}%")
                    ->orWhereHas('vendor', function ($q) use ($search) {
                        $q->where('name', 'like', "%{$search}%");
                    });
            });
        }

        // Filter by vendor name
        if ($vendorName = $request->get('vendor_name')) {
            $query->whereHas('vendor', function ($q) use ($vendorName) {
                $q->where('name', 'like', "%{$vendorName}%");
            });
        }

        // Filter by ticket number
        if ($ticketNumber = $request->get('ticket_number')) {
            $query->where('number', 'like', "%{$ticketNumber}%");
        }

        // Filter by date
        if ($date = $request->get('date')) {
            $query->whereDate('date', $date);
        }

        // Filter by status
        if ($status = $request->get('status')) {
            $query->where('status', $status);
        }

        // Sorting
        $sortField = $request->get('sort', 'created_at');
        $sortDirection = $request->get('direction', 'desc');
        $query->orderBy($sortField, $sortDirection);

        $tickets = $query->paginate(15)->withQueryString();

        return Inertia::render('Tickets/View', [
            'tickets' => $tickets,
            'filters' => $request->only([
                'search', 'vendor_name', 'ticket_number',
                'date', 'status', 'sort', 'direction'
            ]),
        ]);
    }
}
