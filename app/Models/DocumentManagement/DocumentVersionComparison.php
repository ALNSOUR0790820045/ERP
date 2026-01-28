<?php

namespace App\Models\DocumentManagement;

use App\Models\Document;
use App\Models\DocumentRevision;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Document Version Comparison Model
 * مقارنة إصدارات المستندات
 */
class DocumentVersionComparison extends Model
{
    protected $fillable = [
        'document_id',
        'source_revision_id',
        'target_revision_id',
        'comparison_type',
        'status',
        'differences_count',
        'additions_count',
        'deletions_count',
        'modifications_count',
        'diff_data',
        'summary',
        'compared_by',
        'compared_at',
        'metadata',
    ];

    protected $casts = [
        'diff_data' => 'array',
        'metadata' => 'array',
        'compared_at' => 'datetime',
        'differences_count' => 'integer',
        'additions_count' => 'integer',
        'deletions_count' => 'integer',
        'modifications_count' => 'integer',
    ];

    // Comparison Types
    const TYPE_TEXT = 'text';
    const TYPE_VISUAL = 'visual';
    const TYPE_METADATA = 'metadata';
    const TYPE_FULL = 'full';

    // Status Constants
    const STATUS_PENDING = 'pending';
    const STATUS_PROCESSING = 'processing';
    const STATUS_COMPLETED = 'completed';
    const STATUS_FAILED = 'failed';

    public function document(): BelongsTo
    {
        return $this->belongsTo(Document::class);
    }

    public function sourceRevision(): BelongsTo
    {
        return $this->belongsTo(DocumentRevision::class, 'source_revision_id');
    }

    public function targetRevision(): BelongsTo
    {
        return $this->belongsTo(DocumentRevision::class, 'target_revision_id');
    }

    public function comparedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'compared_by');
    }

    // Scopes
    public function scopeForDocument($query, int $documentId)
    {
        return $query->where('document_id', $documentId);
    }

    public function scopeCompleted($query)
    {
        return $query->where('status', self::STATUS_COMPLETED);
    }

    public function scopePending($query)
    {
        return $query->where('status', self::STATUS_PENDING);
    }

    // Helper Methods
    public function isCompleted(): bool
    {
        return $this->status === self::STATUS_COMPLETED;
    }

    public function isPending(): bool
    {
        return $this->status === self::STATUS_PENDING;
    }

    public function getTotalDifferences(): int
    {
        return $this->additions_count + $this->deletions_count + $this->modifications_count;
    }

    public function getChangePercentage(): float
    {
        if (!$this->diff_data || !isset($this->diff_data['total_lines'])) {
            return 0;
        }
        
        $totalLines = $this->diff_data['total_lines'];
        if ($totalLines === 0) return 0;
        
        return ($this->getTotalDifferences() / $totalLines) * 100;
    }

    public function markAsProcessing(): void
    {
        $this->update(['status' => self::STATUS_PROCESSING]);
    }

    public function markAsCompleted(array $diffData, string $summary = null): void
    {
        $this->update([
            'status' => self::STATUS_COMPLETED,
            'diff_data' => $diffData,
            'summary' => $summary,
            'compared_at' => now(),
            'differences_count' => ($diffData['additions'] ?? 0) + ($diffData['deletions'] ?? 0) + ($diffData['modifications'] ?? 0),
            'additions_count' => $diffData['additions'] ?? 0,
            'deletions_count' => $diffData['deletions'] ?? 0,
            'modifications_count' => $diffData['modifications'] ?? 0,
        ]);
    }

    public function markAsFailed(string $error): void
    {
        $this->update([
            'status' => self::STATUS_FAILED,
            'metadata' => array_merge($this->metadata ?? [], ['error' => $error]),
        ]);
    }

    public static function compare(
        Document $document,
        DocumentRevision $source,
        DocumentRevision $target,
        User $user,
        string $type = self::TYPE_FULL
    ): self {
        return static::create([
            'document_id' => $document->id,
            'source_revision_id' => $source->id,
            'target_revision_id' => $target->id,
            'comparison_type' => $type,
            'status' => self::STATUS_PENDING,
            'compared_by' => $user->id,
        ]);
    }

    public static function getLatestComparison(Document $document): ?self
    {
        return static::forDocument($document->id)
            ->completed()
            ->latest('compared_at')
            ->first();
    }

    public function generateSummaryAr(): string
    {
        $parts = [];
        
        if ($this->additions_count > 0) {
            $parts[] = "{$this->additions_count} إضافة";
        }
        if ($this->deletions_count > 0) {
            $parts[] = "{$this->deletions_count} حذف";
        }
        if ($this->modifications_count > 0) {
            $parts[] = "{$this->modifications_count} تعديل";
        }

        if (empty($parts)) {
            return 'لا توجد تغييرات';
        }

        return 'التغييرات: ' . implode('، ', $parts);
    }
}
