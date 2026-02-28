<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TicketApprovalStep extends Model
{
    use HasFactory;

    protected $fillable = [
        'ticket_id',
        'approver_user_id',
        'sequence_no',
        'status',
        'acted_at',
        'remarks',
    ];

    protected function casts(): array
    {
        return [
            'sequence_no' => 'integer',
            'acted_at' => 'datetime',
        ];
    }

    public function ticket(): BelongsTo
    {
        return $this->belongsTo(Ticket::class);
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approver_user_id');
    }

    public function isPending(): bool
    {
        return $this->status === 'pending';
    }

    public function isApproved(): bool
    {
        return $this->status === 'approved';
    }

    public function isRejected(): bool
    {
        return $this->status === 'rejected';
    }

    /**
     * Approve this step.
     */
    public function approve(?string $remarks = null): void
    {
        $this->update([
            'status' => 'approved',
            'acted_at' => now(),
            'remarks' => $remarks,
        ]);
    }

    /**
     * Reject this step (terminal for the ticket).
     */
    public function reject(?string $remarks = null): void
    {
        $this->update([
            'status' => 'rejected',
            'acted_at' => now(),
            'remarks' => $remarks,
        ]);
    }
}
