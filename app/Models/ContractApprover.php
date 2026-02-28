<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ContractApprover extends Model
{
    use HasFactory;

    protected $fillable = [
        'contract_id',
        'user_id',
        'sequence_no',
        'remarks',
        'is_master',
    ];

    protected function casts(): array
    {
        return [
            'sequence_no' => 'integer',
            'is_master' => 'boolean',
        ];
    }

    public function contract(): BelongsTo
    {
        return $this->belongsTo(Contract::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
