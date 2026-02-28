<?php

namespace App\Http\Controllers;

use App\Models\Contract;
use App\Models\Ticket;
use App\Models\TicketApprovalStep;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class PaymentTrackerController extends Controller
{
    /**
     * Payment tracker index — dedicated page listing all tickets as payment requests.
     */
    public function index(Request $request): Response
    {
        $user = $request->user();

        $query = Ticket::query()
            ->with([
                'vendor:id,code,name',
                'contract:id,number,amount,cooperation_type,payment_total_paid,payment_balance',
                'approvalSteps.approver:id,name',
                'createdBy:id,name',
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
                    })
                    ->orWhereHas('contract', function ($q) use ($search) {
                        $q->where('number', 'like', "%{$search}%");
                    });
            });
        }

        // Filter by approval status
        if ($approvalStatus = $request->get('approval_status')) {
            $query->where('approval_status', $approvalStatus);
        }

        // Filter by contract
        if ($contractId = $request->get('contract_id')) {
            $query->where('contract_id', $contractId);
        }

        // Filter by vendor
        if ($vendorId = $request->get('vendor_id')) {
            $query->where('vendor_id', $vendorId);
        }

        // Filter by date range
        if ($dateFrom = $request->get('date_from')) {
            $query->whereDate('date', '>=', $dateFrom);
        }
        if ($dateTo = $request->get('date_to')) {
            $query->whereDate('date', '<=', $dateTo);
        }

        // Sorting
        $sortField = $request->get('sort', 'created_at');
        $sortDirection = $request->get('direction', 'desc');
        $query->orderBy($sortField, $sortDirection);

        $tickets = $query->paginate(15)->withQueryString();

        return Inertia::render('PaymentTracker/Index', [
            'tickets' => $tickets,
            'filters' => $request->only([
                'search', 'approval_status', 'contract_id', 'vendor_id',
                'date_from', 'date_to', 'sort', 'direction'
            ]),
        ]);
    }

    /**
     * Show payment detail for a ticket.
     */
    public function show(Ticket $ticket): Response
    {
        $user = request()->user();

        // Non-admin users must be a stakeholder of the ticket's contract
        if (!$user->isAdmin()) {
            $contractIds = $user->stakeholderContractIds();
            if (!in_array($ticket->contract_id, $contractIds)) {
                abort(403, 'You do not have access to this payment request.');
            }
        }

        $ticket->load([
            'vendor',
            'contract.approvers.user',
            'contract.createdBy:id,name',
            'contract.assignedMaster:id,name',
            'documents',
            'approvalSteps.approver:id,name,email',
            'createdBy',
            'updatedBy',
            'replacesTicket:id,number',
            'replacedByTickets:id,number,replaces_ticket_id',
        ]);

        return Inertia::render('PaymentTracker/Show', [
            'ticket' => $ticket,
        ]);
    }

    /**
     * Submit a ticket for approval.
     * Creates approval steps from contract approvers.
     */
    public function submit(Request $request, Ticket $ticket): RedirectResponse
    {
        $user = $request->user();

        // Non-admin users must be a stakeholder of the ticket's contract
        if (!$user->isAdmin()) {
            $contractIds = $user->stakeholderContractIds();
            if (!in_array($ticket->contract_id, $contractIds)) {
                abort(403, 'You do not have access to this payment request.');
            }
        }

        if ($ticket->approval_status !== 'draft') {
            return redirect()
                ->back()
                ->with('error', 'Only draft tickets can be submitted for approval.');
        }

        // Ensure the ticket has an amount
        if (!$ticket->amount || $ticket->amount <= 0) {
            return redirect()
                ->back()
                ->with('error', 'Ticket must have a payment amount before submission.');
        }

        // Ensure the contract has approvers configured
        $contract = $ticket->contract;
        if (!$contract || $contract->approvers()->count() === 0) {
            return redirect()
                ->back()
                ->with('error', 'The contract must have at least one approver configured.');
        }

        // Validate total won't exceed contract amount
        $currentPaid = $contract->payment_total_paid;
        $pendingAmount = $contract->tickets()
            ->whereIn('approval_status', ['pending', 'approved'])
            ->where('id', '!=', $ticket->id)
            ->sum('amount');

        if (($currentPaid + $pendingAmount + $ticket->amount) > $contract->amount) {
            return redirect()
                ->back()
                ->with('error', 'Total payments (including this ticket) would exceed the contract amount.');
        }

        $ticket->submitForApproval();

        return redirect()
            ->back()
            ->with('success', 'Ticket submitted for approval.');
    }

    /**
     * Approve a ticket approval step.
     * All approvers must approve for the ticket to proceed.
     */
    public function approve(Request $request, Ticket $ticket): RedirectResponse
    {
        $user = $request->user();

        // Non-admin users must be a stakeholder of the ticket's contract
        if (!$user->isAdmin()) {
            $contractIds = $user->stakeholderContractIds();
            if (!in_array($ticket->contract_id, $contractIds)) {
                abort(403, 'You do not have access to this payment request.');
            }
        }

        $step = $ticket->approvalSteps()
            ->where('approver_user_id', $user->id)
            ->where('status', 'pending')
            ->first();

        if (!$step) {
            return redirect()
                ->back()
                ->with('error', 'You do not have a pending approval step for this ticket.');
        }

        // Ensure it's this user's turn (sequence-based)
        $nextPending = $ticket->nextPendingStep();
        if (!$nextPending || $nextPending->id !== $step->id) {
            return redirect()
                ->back()
                ->with('error', 'It is not your turn to approve this ticket.');
        }

        $request->validate([
            'remarks' => ['nullable', 'string', 'max:2000'],
        ]);

        $step->approve($request->input('remarks'));

        // Check if all steps are now approved
        if ($ticket->fresh()->isFullyApproved()) {
            $ticket->markApproved();
        }

        return redirect()
            ->back()
            ->with('success', 'Approval step completed.');
    }

    /**
     * Reject a ticket approval step.
     * Immediate terminal rejection — user can re-request via new ticket later.
     */
    public function reject(Request $request, Ticket $ticket): RedirectResponse
    {
        $user = $request->user();

        // Non-admin users must be a stakeholder of the ticket's contract
        if (!$user->isAdmin()) {
            $contractIds = $user->stakeholderContractIds();
            if (!in_array($ticket->contract_id, $contractIds)) {
                abort(403, 'You do not have access to this payment request.');
            }
        }

        $step = $ticket->approvalSteps()
            ->where('approver_user_id', $user->id)
            ->where('status', 'pending')
            ->first();

        if (!$step) {
            return redirect()
                ->back()
                ->with('error', 'You do not have a pending approval step for this ticket.');
        }

        // Ensure it's this user's turn
        $nextPending = $ticket->nextPendingStep();
        if (!$nextPending || $nextPending->id !== $step->id) {
            return redirect()
                ->back()
                ->with('error', 'It is not your turn to act on this ticket.');
        }

        $request->validate([
            'remarks' => ['required', 'string', 'max:2000'],
        ]);

        $step->reject($request->input('remarks'));
        $ticket->markRejected();

        return redirect()
            ->back()
            ->with('success', 'Ticket has been rejected.');
    }

    /**
     * Mark an approved ticket as paid.
     * Only contract masters can mark as paid.
     */
    public function markPaid(Request $request, Ticket $ticket): RedirectResponse
    {
        $user = $request->user();

        // Non-admin users must be a stakeholder of the ticket's contract
        if (!$user->isAdmin()) {
            $contractIds = $user->stakeholderContractIds();
            if (!in_array($ticket->contract_id, $contractIds)) {
                abort(403, 'You do not have access to this payment request.');
            }
        }

        if ($ticket->approval_status !== 'approved') {
            return redirect()
                ->back()
                ->with('error', 'Only approved tickets can be marked as paid.');
        }

        $contract = $ticket->contract;
        $user = $request->user();

        if (!$contract || !$contract->isContractMaster($user)) {
            return redirect()
                ->back()
                ->with('error', 'Only contract masters can mark tickets as paid.');
        }

        $request->validate([
            'reference_no' => ['nullable', 'string', 'max:100'],
        ]);

        $ticket->markPaid($request->input('reference_no'));

        return redirect()
            ->back()
            ->with('success', 'Payment recorded successfully.');
    }
}
