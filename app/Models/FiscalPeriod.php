<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FiscalPeriod extends Model
{
    protected $fillable = [
        'fiscal_year_id',
        'period_number',
        'name',
        'start_date',
        'end_date',
        'status',
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
        'period_number' => 'integer',
    ];

    public function fiscalYear(): BelongsTo
    {
        return $this->belongsTo(FiscalYear::class);
    }

    public function isOpen(): bool
    {
        return $this->status === 'open';
    }

    public function close(): void
    {
        $this->update(['status' => 'closed']);
    }

    public function reopen(): void
    {
        if ($this->fiscalYear->isOpen()) {
            $this->update(['status' => 'open']);
        }
    }

    public function getStatusLabel(): string
    {
        return match($this->status) {
            'open' => 'مفتوحة',
            'closed' => 'مقفلة',
            default => $this->status,
        };
    }

    public function getStatusColor(): string
    {
        return match($this->status) {
            'open' => 'success',
            'closed' => 'danger',
            default => 'gray',
        };
    }

    public function containsDate($date): bool
    {
        $date = is_string($date) ? now()->parse($date) : $date;
        return $date->between($this->start_date, $this->end_date);
    }

    public static function findByDate($date, $companyId = null)
    {
        $query = self::whereDate('start_date', '<=', $date)
            ->whereDate('end_date', '>=', $date);
            
        if ($companyId) {
            $query->whereHas('fiscalYear', fn($q) => $q->where('company_id', $companyId));
        }
        
        return $query->first();
    }
}
