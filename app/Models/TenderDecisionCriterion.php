<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TenderDecisionCriterion extends Model
{
    protected $table = 'tender_decision_criteria';

    protected $fillable = [
        'tender_id',
        'criterion',
        'weight',
        'score',
        'weighted_score',
        'notes',
    ];

    protected $casts = [
        'weight' => 'decimal:2',
        'score' => 'integer',
        'weighted_score' => 'decimal:2',
    ];

    protected static function booted(): void
    {
        static::saving(function ($model) {
            $model->weighted_score = ($model->weight / 100) * $model->score;
        });
    }

    public function tender(): BelongsTo
    {
        return $this->belongsTo(Tender::class);
    }
}
