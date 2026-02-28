<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Ticket extends Model
{
    use HasFactory, SoftDeletes;

    public const DOCUMENT_TYPES = [
        'contract',
        'invoice',
        'handover_report',
        'tax_id',
        'tax_invoice',
    ];

    public const TOTAL_REQUIRED_DOCUMENTS = 5;

    protected $fillable = [
        'number',
        'date',
        'contract_id',
        'vendor_id',
        'status',
        'amount',
        'approval_status',
        'submitted_at',
        'approved_at',
        'paid_at',
        'reference_no',
        'replaces_ticket_id',
        'notes',
        'is_active',
        'created_by_user_id',
        'updated_by_user_id',
    ];

    protected $appends = [
        'document_count',
    ];

    protected function casts(): array
    {
        return [
            'date' => 'date',
            'amount' => 'decimal:2',
            'is_active' => 'boolean',
            'submitted_at' => 'datetime',
            'approved_at' => 'datetime',
            'paid_at' => 'datetime',
        ];
    }

    public function contract(): BelongsTo
    {
        return $this->belongsTo(Contract::class);
    }

    public function vendor(): BelongsTo
    {
        return $this->belongsTo(Vendor::class);
    }

    public function documents(): HasMany
    {
        return $this->hasMany(Document::class);
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by_user_id');
    }

    public function updatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by_user_id');
    }

    /**
     * The ticket this one replaces (re-request after rejection).
     */
    public function replacesTicket(): BelongsTo
    {
        return $this->belongsTo(Ticket::class, 'replaces_ticket_id');
    }

    /**
     * Tickets that replaced this one.
     */
    public function replacedByTickets(): HasMany
    {
        return $this->hasMany(Ticket::class, 'replaces_ticket_id');
    }

    /**
     * Approval steps for this ticket.
     */
    public function approvalSteps(): HasMany
    {
        return $this->hasMany(TicketApprovalStep::class)->orderBy('sequence_no');
    }

    /**
     * Submit ticket for approval. Snapshots contract approvers into ticket_approval_steps.
     */
    public function submitForApproval(): void
    {
        $contract = $this->contract;

        if (!$contract) {
            return;
        }

        $approvers = $contract->approvers()->with('user')->get();

        // Create approval steps from contract approvers
        foreach ($approvers as $approver) {
            TicketApprovalStep::create([
                'ticket_id' => $this->id,
                'approver_user_id' => $approver->user_id,
                'sequence_no' => $approver->sequence_no,
                'status' => 'pending',
            ]);
        }

        $this->update([
            'approval_status' => 'pending',
            'submitted_at' => now(),
        ]);
    }

    /**
     * Get the next pending approval step (sequence-based).
     */
    public function nextPendingStep(): ?TicketApprovalStep
    {
        return $this->approvalSteps()
            ->where('status', 'pending')
            ->orderBy('sequence_no')
            ->first();
    }

    /**
     * Check if all approvers have approved.
     */
    public function isFullyApproved(): bool
    {
        return $this->approvalSteps()->count() > 0
            && $this->approvalSteps()->where('status', '!=', 'approved')->doesntExist();
    }

    /**
     * Check if this ticket has been rejected.
     */
    public function isRejected(): bool
    {
        return $this->approval_status === 'rejected';
    }

    /**
     * Mark ticket as approved (all approvers passed).
     */
    public function markApproved(): void
    {
        $this->update([
            'approval_status' => 'approved',
            'approved_at' => now(),
        ]);
    }

    /**
     * Mark ticket as rejected (terminal).
     */
    public function markRejected(): void
    {
        $this->update([
            'approval_status' => 'rejected',
        ]);
    }

    /**
     * Mark ticket as paid and sync contract payment cache.
     */
    public function markPaid(?string $referenceNo = null): void
    {
        $this->update([
            'approval_status' => 'paid',
            'paid_at' => now(),
            'reference_no' => $referenceNo,
        ]);

        // Sync contract payment cache
        $this->contract?->syncPaymentCache();
    }

    public function getDocumentCountAttribute(): int
    {
        return $this->documents()->count();
    }

    public function getCompletenessAttribute(): string
    {
        return "{$this->document_count}/" . self::TOTAL_REQUIRED_DOCUMENTS . " documents";
    }

    public function updateStatus(): void
    {
        $this->status = $this->document_count >= self::TOTAL_REQUIRED_DOCUMENTS
            ? 'complete'
            : 'incomplete';
        $this->save();
    }

    public static function generateNumber(): string
    {
        $year = now()->year;
        $prefix = "TKT-{$year}-";

        $lastTicket = static::where('number', 'like', "{$prefix}%")
            ->orderByDesc('number')
            ->first();

        if ($lastTicket) {
            $lastNumber = (int) substr($lastTicket->number, -4);
            $newNumber = $lastNumber + 1;
        } else {
            $newNumber = 1;
        }

        return $prefix . str_pad($newNumber, 4, '0', STR_PAD_LEFT);
    }
}
