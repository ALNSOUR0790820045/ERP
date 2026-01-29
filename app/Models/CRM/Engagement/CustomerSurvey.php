<?php

namespace App\Models\CRM\Engagement;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CustomerSurvey extends Model
{
    protected $fillable = [
        'code',
        'title_ar',
        'title_en',
        'description',
        'survey_type',
        'questions',
        'start_date',
        'end_date',
        'status',
        'response_count',
        'average_score',
        'created_by',
    ];

    protected $casts = [
        'questions' => 'array',
        'start_date' => 'date',
        'end_date' => 'date',
        'response_count' => 'integer',
        'average_score' => 'decimal:2',
    ];

    // العلاقات
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function responses(): HasMany
    {
        return $this->hasMany(SurveyResponse::class, 'survey_id');
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('status', 'active')
            ->where(function ($q) {
                $q->whereNull('start_date')->orWhere('start_date', '<=', now());
            })
            ->where(function ($q) {
                $q->whereNull('end_date')->orWhere('end_date', '>=', now());
            });
    }

    public function scopeByType($query, string $type)
    {
        return $query->where('survey_type', $type);
    }

    // Accessors
    public function getTitleAttribute(): string
    {
        return app()->getLocale() === 'ar' ? $this->title_ar : $this->title_en;
    }

    // Methods
    public function activate(): void
    {
        $this->update(['status' => 'active']);
    }

    public function close(): void
    {
        $this->update(['status' => 'closed']);
    }

    public function isActive(): bool
    {
        if ($this->status !== 'active') return false;
        if ($this->start_date && $this->start_date > now()) return false;
        if ($this->end_date && $this->end_date < now()) return false;
        return true;
    }

    public function updateStats(): void
    {
        $this->response_count = $this->responses()->count();
        $this->average_score = $this->responses()->avg('score');
        $this->save();
    }

    public function getNpsScore(): ?float
    {
        if ($this->survey_type !== 'nps') return null;
        
        $responses = $this->responses()->whereNotNull('nps_score')->get();
        if ($responses->isEmpty()) return null;
        
        $promoters = $responses->where('nps_score', '>=', 9)->count();
        $detractors = $responses->where('nps_score', '<=', 6)->count();
        $total = $responses->count();
        
        return (($promoters - $detractors) / $total) * 100;
    }

    public function getQuestionStats(): array
    {
        $stats = [];
        
        foreach ($this->questions ?? [] as $index => $question) {
            $answers = $this->responses()
                ->get()
                ->pluck("answers.{$index}")
                ->filter();
                
            $stats[$index] = [
                'question' => $question['text'] ?? '',
                'type' => $question['type'] ?? 'text',
                'total_responses' => $answers->count(),
                'answers' => $question['type'] === 'rating' 
                    ? ['average' => $answers->avg()]
                    : $answers->countBy()->toArray(),
            ];
        }
        
        return $stats;
    }
}
