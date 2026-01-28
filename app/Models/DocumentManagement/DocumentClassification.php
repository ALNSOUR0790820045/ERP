<?php

namespace App\Models\DocumentManagement;

use App\Models\Document;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Document Classification Model
 * تصنيف المستندات بالذكاء الاصطناعي
 */
class DocumentClassification extends Model
{
    protected $fillable = [
        'document_id',
        'ai_model',
        'predicted_category',
        'predicted_type',
        'predicted_discipline',
        'predicted_tags',
        'predicted_metadata',
        'confidence_score',
        'all_predictions',
        'status',
        'is_accepted',
        'reviewed_by',
        'reviewed_at',
        'review_notes',
        'extracted_entities',
        'summary',
        'metadata',
    ];

    protected $casts = [
        'predicted_tags' => 'array',
        'predicted_metadata' => 'array',
        'all_predictions' => 'array',
        'extracted_entities' => 'array',
        'metadata' => 'array',
        'confidence_score' => 'decimal:2',
        'is_accepted' => 'boolean',
        'reviewed_at' => 'datetime',
    ];

    // Status Constants
    const STATUS_PENDING = 'pending';
    const STATUS_PROCESSING = 'processing';
    const STATUS_COMPLETED = 'completed';
    const STATUS_FAILED = 'failed';
    const STATUS_REVIEWED = 'reviewed';

    // AI Model Constants
    const MODEL_GPT4 = 'gpt-4';
    const MODEL_CLAUDE = 'claude-3';
    const MODEL_GEMINI = 'gemini-pro';
    const MODEL_CUSTOM = 'custom';

    public function document(): BelongsTo
    {
        return $this->belongsTo(Document::class);
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
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

    public function scopeAccepted($query)
    {
        return $query->where('is_accepted', true);
    }

    public function scopeNeedsReview($query)
    {
        return $query->where('status', self::STATUS_COMPLETED)
            ->whereNull('reviewed_at');
    }

    public function scopeHighConfidence($query, float $minScore = 0.85)
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

    public function isAccepted(): bool
    {
        return $this->is_accepted === true;
    }

    public function needsReview(): bool
    {
        return $this->isCompleted() && is_null($this->reviewed_at);
    }

    public function accept(User $reviewer, ?string $notes = null): void
    {
        $this->update([
            'is_accepted' => true,
            'reviewed_by' => $reviewer->id,
            'reviewed_at' => now(),
            'review_notes' => $notes,
            'status' => self::STATUS_REVIEWED,
        ]);

        // Apply classification to document
        $this->applyToDocument();
    }

    public function reject(User $reviewer, string $notes): void
    {
        $this->update([
            'is_accepted' => false,
            'reviewed_by' => $reviewer->id,
            'reviewed_at' => now(),
            'review_notes' => $notes,
            'status' => self::STATUS_REVIEWED,
        ]);
    }

    public function applyToDocument(): void
    {
        $document = $this->document;
        
        if ($this->predicted_category) {
            $document->update(['category' => $this->predicted_category]);
        }
        
        if ($this->predicted_type) {
            $document->update(['document_type' => $this->predicted_type]);
        }
        
        if ($this->predicted_discipline) {
            $document->update(['discipline' => $this->predicted_discipline]);
        }
        
        $document->update(['ai_classified' => true]);
    }

    public function getTopPrediction(): ?array
    {
        if (!$this->all_predictions) return null;
        
        return collect($this->all_predictions)
            ->sortByDesc('confidence')
            ->first();
    }

    public function getEntitiesByType(string $type): array
    {
        if (!$this->extracted_entities) return [];
        
        return collect($this->extracted_entities)
            ->where('type', $type)
            ->values()
            ->toArray();
    }
}
