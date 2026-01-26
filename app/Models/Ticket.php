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
            'is_active' => 'boolean',
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
