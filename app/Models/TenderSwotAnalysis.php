<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TenderSwotAnalysis extends Model
{
    protected $fillable = [
        'tender_id',
        'type',
        'description',
        'impact_level',
        'created_by',
    ];

    protected $casts = [
        'impact_level' => 'integer',
    ];

    public function tender(): BelongsTo
    {
        return $this->belongsTo(Tender::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function getTypeNameAttribute(): string
    {
        return match($this->type) {
            'strength' => 'نقطة قوة',
            'weakness' => 'نقطة ضعف',
            'opportunity' => 'فرصة',
            'threat' => 'تهديد',
            default => $this->type,
        };
    }
}
