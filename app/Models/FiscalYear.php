<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class FiscalYear extends Model
{
    protected $fillable = [
        'company_id',
        'name',
        'start_date',
        'end_date',
        'status',
        'is_current',
        'created_by',
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
        'is_current' => 'boolean',
    ];

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function periods(): HasMany
    {
        return $this->hasMany(FiscalPeriod::class);
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function isOpen(): bool
    {
        return $this->status === 'open';
    }

    public function close(): bool
    {
        if ($this->periods()->where('status', 'open')->exists()) {
            return false;
        }
        
        $this->update(['status' => 'closed', 'is_current' => false]);
        return true;
    }

    public function reopen(): void
    {
        $this->update(['status' => 'open']);
    }

    public function makeCurrent(): void
    {
        // Remove current from other years
        self::where('company_id', $this->company_id)
            ->where('id', '!=', $this->id)
            ->update(['is_current' => false]);
            
        $this->update(['is_current' => true]);
    }

    public function generatePeriods(): void
    {
        $startDate = $this->start_date->copy();
        $periodNumber = 1;
        
        while ($startDate->lt($this->end_date)) {
            $endDate = $startDate->copy()->endOfMonth();
            
            if ($endDate->gt($this->end_date)) {
                $endDate = $this->end_date->copy();
            }
            
            $this->periods()->create([
                'period_number' => $periodNumber,
                'name' => $startDate->translatedFormat('F Y'),
                'start_date' => $startDate,
                'end_date' => $endDate,
                'status' => 'open',
            ]);
            
            $startDate = $endDate->copy()->addDay();
            $periodNumber++;
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
}
