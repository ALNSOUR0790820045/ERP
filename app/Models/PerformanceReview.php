<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PerformanceReview extends Model
{
    use HasFactory;

    protected $fillable = [
        'employee_id', 'review_period', 'review_date', 'reviewer_id',
        'overall_rating', 'goals_achieved', 'strengths', 'areas_for_improvement',
        'goals_next_period', 'training_needs', 'promotion_recommendation',
        'salary_recommendation', 'employee_comments', 'manager_comments',
        'status', 'acknowledged_at', 'notes',
    ];

    protected $casts = [
        'review_date' => 'date',
        'acknowledged_at' => 'datetime',
        'overall_rating' => 'decimal:2',
        'goals_achieved' => 'decimal:2',
        'promotion_recommendation' => 'boolean',
        'strengths' => 'array',
        'areas_for_improvement' => 'array',
        'goals_next_period' => 'array',
        'training_needs' => 'array',
    ];

    public function employee(): BelongsTo { return $this->belongsTo(Employee::class); }
    public function reviewer(): BelongsTo { return $this->belongsTo(User::class, 'reviewer_id'); }

    public function scopeCompleted($query) { return $query->where('status', 'completed'); }
    public function scopePending($query) { return $query->where('status', 'pending'); }
}
