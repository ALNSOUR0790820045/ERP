<?php

namespace App\Models\CRM\Service;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class KnowledgeBase extends Model
{
    use SoftDeletes;

    protected $table = 'knowledge_base';

    protected $fillable = [
        'article_number',
        'title_ar',
        'title_en',
        'category_id',
        'content_ar',
        'content_en',
        'keywords',
        'visibility',
        'status',
        'view_count',
        'helpful_count',
        'not_helpful_count',
        'created_by',
        'approved_by',
        'published_at',
    ];

    protected $casts = [
        'keywords' => 'array',
        'view_count' => 'integer',
        'helpful_count' => 'integer',
        'not_helpful_count' => 'integer',
        'published_at' => 'datetime',
    ];

    // Boot
    protected static function boot()
    {
        parent::boot();
        
        static::creating(function ($article) {
            if (empty($article->article_number)) {
                $article->article_number = 'KB-' . str_pad(static::max('id') + 1, 6, '0', STR_PAD_LEFT);
            }
        });
    }

    // العلاقات
    public function category(): BelongsTo
    {
        return $this->belongsTo(CaseCategory::class, 'category_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    // Scopes
    public function scopePublished($query)
    {
        return $query->where('status', 'published');
    }

    public function scopePublic($query)
    {
        return $query->where('visibility', 'public');
    }

    public function scopeInternal($query)
    {
        return $query->where('visibility', 'internal');
    }

    public function scopeForCategory($query, int $categoryId)
    {
        return $query->where('category_id', $categoryId);
    }

    public function scopeSearch($query, string $term)
    {
        return $query->where(function ($q) use ($term) {
            $q->where('title_ar', 'like', "%{$term}%")
              ->orWhere('title_en', 'like', "%{$term}%")
              ->orWhere('content_ar', 'like', "%{$term}%")
              ->orWhere('content_en', 'like', "%{$term}%")
              ->orWhereJsonContains('keywords', $term);
        });
    }

    public function scopePopular($query, int $limit = 10)
    {
        return $query->published()->orderByDesc('view_count')->limit($limit);
    }

    // Accessors
    public function getTitleAttribute(): string
    {
        return app()->getLocale() === 'ar' ? $this->title_ar : $this->title_en;
    }

    public function getContentAttribute(): string
    {
        return app()->getLocale() === 'ar' ? $this->content_ar : ($this->content_en ?? $this->content_ar);
    }

    public function getHelpfulnessRateAttribute(): float
    {
        $total = $this->helpful_count + $this->not_helpful_count;
        return $total > 0 ? ($this->helpful_count / $total) * 100 : 0;
    }

    // Methods
    public function publish(): void
    {
        $this->update([
            'status' => 'published',
            'published_at' => now(),
            'approved_by' => auth()->id(),
        ]);
    }

    public function archive(): void
    {
        $this->update(['status' => 'archived']);
    }

    public function incrementViewCount(): void
    {
        $this->increment('view_count');
    }

    public function markHelpful(): void
    {
        $this->increment('helpful_count');
    }

    public function markNotHelpful(): void
    {
        $this->increment('not_helpful_count');
    }

    public function isPublished(): bool
    {
        return $this->status === 'published';
    }

    public function isVisibleTo(?User $user = null): bool
    {
        if ($this->visibility === 'public') return true;
        if (!$user) return false;
        if ($this->visibility === 'internal') return true;
        if ($this->visibility === 'agents_only') {
            // التحقق من صلاحية الوكيل
            return $user->hasAnyRole(['admin', 'support_agent']);
        }
        
        return false;
    }
}
