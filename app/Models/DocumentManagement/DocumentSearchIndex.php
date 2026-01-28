<?php

namespace App\Models\DocumentManagement;

use App\Models\Document;
use App\Models\DocumentRevision;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Document Search Index Model
 * فهرس البحث النصي الكامل
 */
class DocumentSearchIndex extends Model
{
    protected $table = 'document_search_index';

    protected $fillable = [
        'document_id',
        'revision_id',
        'content',
        'title_text',
        'metadata_text',
        'keywords',
        'entities',
        'indexed_at',
        'index_status',
    ];

    protected $casts = [
        'keywords' => 'array',
        'entities' => 'array',
        'indexed_at' => 'datetime',
    ];

    // Status Constants
    const STATUS_ACTIVE = 'active';
    const STATUS_PENDING = 'pending';
    const STATUS_OUTDATED = 'outdated';
    const STATUS_DELETED = 'deleted';

    public function document(): BelongsTo
    {
        return $this->belongsTo(Document::class);
    }

    public function revision(): BelongsTo
    {
        return $this->belongsTo(DocumentRevision::class);
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('index_status', self::STATUS_ACTIVE);
    }

    public function scopeSearchContent($query, string $searchTerm)
    {
        return $query->where(function ($q) use ($searchTerm) {
            $q->where('content', 'like', "%{$searchTerm}%")
              ->orWhere('title_text', 'like', "%{$searchTerm}%")
              ->orWhere('metadata_text', 'like', "%{$searchTerm}%");
        });
    }

    public function scopeHasKeyword($query, string $keyword)
    {
        return $query->whereJsonContains('keywords', $keyword);
    }

    // Helper Methods
    public function isActive(): bool
    {
        return $this->index_status === self::STATUS_ACTIVE;
    }

    public function markAsOutdated(): void
    {
        $this->update(['index_status' => self::STATUS_OUTDATED]);
    }

    public function reindex(): void
    {
        $document = $this->document;
        
        $this->update([
            'title_text' => $document->title,
            'metadata_text' => $this->buildMetadataText($document),
            'indexed_at' => now(),
            'index_status' => self::STATUS_ACTIVE,
        ]);
    }

    protected function buildMetadataText(Document $document): string
    {
        $parts = [
            $document->document_number,
            $document->description,
            $document->document_type,
            $document->discipline,
        ];

        return implode(' ', array_filter($parts));
    }

    public function extractKeywords(): array
    {
        $text = $this->content . ' ' . $this->title_text . ' ' . $this->metadata_text;
        
        // Remove common stop words and extract meaningful keywords
        $words = preg_split('/\s+/', strtolower($text));
        $stopWords = ['the', 'a', 'an', 'and', 'or', 'but', 'in', 'on', 'at', 'to', 'for', 'of', 'with'];
        
        $keywords = array_filter($words, function ($word) use ($stopWords) {
            return strlen($word) > 3 && !in_array($word, $stopWords);
        });
        
        $wordCounts = array_count_values($keywords);
        arsort($wordCounts);
        
        return array_slice(array_keys($wordCounts), 0, 50);
    }

    public static function search(string $query, array $filters = []): \Illuminate\Database\Eloquent\Collection
    {
        $builder = static::active()->searchContent($query);

        if (!empty($filters['document_type'])) {
            $builder->whereHas('document', function ($q) use ($filters) {
                $q->where('document_type', $filters['document_type']);
            });
        }

        if (!empty($filters['project_id'])) {
            $builder->whereHas('document', function ($q) use ($filters) {
                $q->where('project_id', $filters['project_id']);
            });
        }

        return $builder->with('document')->limit(100)->get();
    }

    public static function indexDocument(Document $document, ?string $content = null): self
    {
        return static::updateOrCreate(
            ['document_id' => $document->id],
            [
                'content' => $content,
                'title_text' => $document->title,
                'metadata_text' => implode(' ', [
                    $document->document_number,
                    $document->description ?? '',
                    $document->document_type ?? '',
                    $document->discipline ?? '',
                ]),
                'indexed_at' => now(),
                'index_status' => self::STATUS_ACTIVE,
            ]
        );
    }
}
