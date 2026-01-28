<?php

namespace App\Models\DocumentManagement;

use App\Models\Document;
use App\Models\DocumentRevision;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Document OCR Result Model
 * نتائج استخراج النص من المستندات
 */
class DocumentOcrResult extends Model
{
    protected $fillable = [
        'document_id',
        'revision_id',
        'file_path',
        'ocr_engine',
        'language',
        'extracted_text',
        'text_blocks',
        'tables',
        'key_value_pairs',
        'confidence_score',
        'status',
        'error_message',
        'pages_processed',
        'total_pages',
        'processing_time_ms',
        'processed_at',
        'metadata',
    ];

    protected $casts = [
        'text_blocks' => 'array',
        'tables' => 'array',
        'key_value_pairs' => 'array',
        'metadata' => 'array',
        'confidence_score' => 'decimal:2',
        'processed_at' => 'datetime',
        'pages_processed' => 'integer',
        'total_pages' => 'integer',
        'processing_time_ms' => 'integer',
    ];

    // Status Constants
    const STATUS_PENDING = 'pending';
    const STATUS_PROCESSING = 'processing';
    const STATUS_COMPLETED = 'completed';
    const STATUS_FAILED = 'failed';

    // OCR Engine Constants
    const ENGINE_TESSERACT = 'tesseract';
    const ENGINE_GOOGLE_VISION = 'google_vision';
    const ENGINE_AWS_TEXTRACT = 'aws_textract';
    const ENGINE_AZURE_OCR = 'azure_ocr';

    public function document(): BelongsTo
    {
        return $this->belongsTo(Document::class);
    }

    public function revision(): BelongsTo
    {
        return $this->belongsTo(DocumentRevision::class);
    }

    // Scopes
    public function scopePending($query)
    {
        return $query->where('status', self::STATUS_PENDING);
    }

    public function scopeCompleted($query)
    {
        return $query->where('status', self::STATUS_COMPLETED);
    }

    public function scopeFailed($query)
    {
        return $query->where('status', self::STATUS_FAILED);
    }

    public function scopeHighConfidence($query, float $minScore = 0.8)
    {
        return $query->where('confidence_score', '>=', $minScore);
    }

    // Helper Methods
    public function isPending(): bool
    {
        return $this->status === self::STATUS_PENDING;
    }

    public function isCompleted(): bool
    {
        return $this->status === self::STATUS_COMPLETED;
    }

    public function isFailed(): bool
    {
        return $this->status === self::STATUS_FAILED;
    }

    public function getProgress(): float
    {
        if ($this->total_pages === 0) return 0;
        return ($this->pages_processed / $this->total_pages) * 100;
    }

    public function markAsProcessing(): void
    {
        $this->update(['status' => self::STATUS_PROCESSING]);
    }

    public function markAsCompleted(string $text, array $blocks = []): void
    {
        $this->update([
            'status' => self::STATUS_COMPLETED,
            'extracted_text' => $text,
            'text_blocks' => $blocks,
            'processed_at' => now(),
        ]);
    }

    public function markAsFailed(string $error): void
    {
        $this->update([
            'status' => self::STATUS_FAILED,
            'error_message' => $error,
            'processed_at' => now(),
        ]);
    }

    public function searchText(string $query): array
    {
        if (!$this->extracted_text) return [];
        
        $matches = [];
        $lines = explode("\n", $this->extracted_text);
        
        foreach ($lines as $lineNumber => $line) {
            if (stripos($line, $query) !== false) {
                $matches[] = [
                    'line' => $lineNumber + 1,
                    'content' => $line,
                ];
            }
        }
        
        return $matches;
    }
}
