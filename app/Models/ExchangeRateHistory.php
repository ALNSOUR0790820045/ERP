<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ExchangeRateHistory extends Model
{
    use HasFactory;

    protected $fillable = [
        'exchange_rate_id', 'old_rate', 'new_rate', 'change_date',
        'change_reason', 'changed_by',
    ];

    protected $casts = [
        'old_rate' => 'decimal:6',
        'new_rate' => 'decimal:6',
        'change_date' => 'datetime',
    ];

    public function exchangeRate(): BelongsTo { return $this->belongsTo(ExchangeRate::class); }
    public function changedBy(): BelongsTo { return $this->belongsTo(User::class, 'changed_by'); }
}
