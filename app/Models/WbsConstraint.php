<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WbsConstraint extends Model
{
    protected $fillable = [
        'wbs_id',
        'constraint_type',
        'constraint_date',
        'reason',
    ];

    protected $casts = [
        'constraint_date' => 'date',
    ];

    public function wbs(): BelongsTo
    {
        return $this->belongsTo(ProjectWbs::class, 'wbs_id');
    }

    public function getConstraintTypeNameAttribute(): string
    {
        return match($this->constraint_type) {
            'ASAP' => 'في أقرب وقت ممكن',
            'ALAP' => 'في أبعد وقت ممكن',
            'MSO' => 'يجب أن يبدأ في',
            'MFO' => 'يجب أن ينتهي في',
            'SNET' => 'لا يبدأ قبل',
            'SNLT' => 'لا يبدأ بعد',
            'FNET' => 'لا ينتهي قبل',
            'FNLT' => 'لا ينتهي بعد',
            default => $this->constraint_type,
        };
    }
}
