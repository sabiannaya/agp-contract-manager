<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Contract extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'number',
        'date',
        'vendor_id',
        'amount',
        'cooperation_type',
        'term_count',
        'term_percentages',
        'is_active',
        'created_by_user_id',
        'updated_by_user_id',
        'assigned_master_user_id',
        'payment_total_paid',
        'payment_balance',
        'payment_last_synced_at',
    ];

    protected function casts(): array
    {
        return [
            'date' => 'date',
            'amount' => 'decimal:2',
            'term_count' => 'integer',
            'term_percentages' => 'array',
            'is_active' => 'boolean',
            'payment_total_paid' => 'decimal:2',
            'payment_balance' => 'decimal:2',
            'payment_last_synced_at' => 'datetime',
        ];
    }

    /**
     * Scope to only contracts where the given user is a stakeholder.
     * A stakeholder is: creator, assigned master, or a configured approver.
     * Admin users bypass this scope (handled in controllers).
     */
    public function scopeForStakeholder(Builder $query, User $user): Builder
    {
        return $query->where(function (Builder $q) use ($user) {
            $q->where('created_by_user_id', $user->id)
                ->orWhere('assigned_master_user_id', $user->id)
                ->orWhereHas('approvers', function (Builder $aq) use ($user) {
                    $aq->where('user_id', $user->id);
                });
        });
    }

    public function vendor(): BelongsTo
    {
        return $this->belongsTo(Vendor::class);
    }

    public function tickets(): HasMany
    {
        return $this->hasMany(Ticket::class);
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
     * The assignable second contract master (creator is auto-master).
     */
    public function assignedMaster(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_master_user_id');
    }

    /**
     * Get the list of configured approvers for this contract.
     */
    public function approvers(): HasMany
    {
        return $this->hasMany(ContractApprover::class)->orderBy('sequence_no');
    }

    /**
     * Check if a user is a contract master (creator or assigned).
     */
    public function isContractMaster(User $user): bool
    {
        return $this->created_by_user_id === $user->id
            || $this->assigned_master_user_id === $user->id;
    }

    /**
     * Get paid tickets for this contract.
     */
    public function paidTickets(): HasMany
    {
        return $this->hasMany(Ticket::class)->where('approval_status', 'paid');
    }

    /**
     * Recalculate and persist cached payment totals.
     */
    public function syncPaymentCache(): void
    {
        $totalPaid = $this->tickets()
            ->where('approval_status', 'paid')
            ->sum('amount');

        $this->update([
            'payment_total_paid' => $totalPaid,
            'payment_balance' => max(0, $this->amount - $totalPaid),
            'payment_last_synced_at' => now(),
        ]);
    }

    public function isProgress(): bool
    {
        return $this->cooperation_type === 'progress';
    }

    public function isRoutine(): bool
    {
        return $this->cooperation_type === 'routine';
    }
}
