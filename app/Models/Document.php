<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

class Document extends Model
{
    use HasFactory;

    public const TYPE_CONTRACT = 'contract';
    public const TYPE_INVOICE = 'invoice';
    public const TYPE_HANDOVER_REPORT = 'handover_report';
    public const TYPE_TAX_ID = 'tax_id';
    public const TYPE_TAX_INVOICE = 'tax_invoice';

    public const TYPES = [
        self::TYPE_CONTRACT,
        self::TYPE_INVOICE,
        self::TYPE_HANDOVER_REPORT,
        self::TYPE_TAX_ID,
        self::TYPE_TAX_INVOICE,
    ];

    public const TYPE_LABELS = [
        self::TYPE_CONTRACT => 'Contract Document',
        self::TYPE_INVOICE => 'Invoice',
        self::TYPE_HANDOVER_REPORT => 'Handover Report (BAST)',
        self::TYPE_TAX_ID => 'Tax ID (NPWP)',
        self::TYPE_TAX_INVOICE => 'Tax Invoice (Faktur Pajak)',
    ];

    public const ALLOWED_MIME_TYPES = [
        'application/pdf',
        'image/jpeg',
        'image/jpg',
        'image/png',
    ];

    public const MAX_FILE_SIZE = 10240; // 10MB in KB

    protected $fillable = [
        'ticket_id',
        'type',
        'original_name',
        'file_path',
        'mime_type',
        'file_size',
        'uploaded_by_user_id',
    ];

    protected function casts(): array
    {
        return [
            'file_size' => 'integer',
        ];
    }

    public function ticket(): BelongsTo
    {
        return $this->belongsTo(Ticket::class);
    }

    public function uploadedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by_user_id');
    }

    public function getTypeLabel(): string
    {
        return self::TYPE_LABELS[$this->type] ?? $this->type;
    }

    public function getFileSizeFormattedAttribute(): string
    {
        $bytes = $this->file_size;

        if ($bytes >= 1048576) {
            return number_format($bytes / 1048576, 2) . ' MB';
        }

        if ($bytes >= 1024) {
            return number_format($bytes / 1024, 2) . ' KB';
        }

        return $bytes . ' bytes';
    }

    public function getUrlAttribute(): string
    {
        return route('documents.download', $this);
    }

    public function deleteFile(): bool
    {
        if ($this->file_path && Storage::disk('local')->exists($this->file_path)) {
            return Storage::disk('local')->delete($this->file_path);
        }

        return false;
    }

    protected static function booted(): void
    {
        static::deleting(function (Document $document) {
            $document->deleteFile();
        });
    }
}
