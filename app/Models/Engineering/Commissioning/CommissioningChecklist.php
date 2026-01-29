<?php

namespace App\Models\Engineering\Commissioning;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CommissioningChecklist extends Model
{
    protected $fillable = [
        'system_id',
        'checklist_number',
        'name',
        'description',
        'checklist_type',
        'phase',
        'total_items',
        'passed_items',
        'failed_items',
        'na_items',
        'scheduled_date',
        'executed_date',
        'executed_by',
        'witnessed_by',
        'status',
        'notes',
    ];

    protected $casts = [
        'scheduled_date' => 'date',
        'executed_date' => 'date',
        'total_items' => 'integer',
        'passed_items' => 'integer',
        'failed_items' => 'integer',
        'na_items' => 'integer',
    ];

    public function system(): BelongsTo
    {
        return $this->belongsTo(CommissioningSystem::class, 'system_id');
    }

    public function executor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'executed_by');
    }

    public function witness(): BelongsTo
    {
        return $this->belongsTo(User::class, 'witnessed_by');
    }

    public function items(): HasMany
    {
        return $this->hasMany(CommissioningChecklistItem::class, 'checklist_id');
    }

    public function updateResults(): void
    {
        $this->total_items = $this->items()->count();
        $this->passed_items = $this->items()->where('result', 'pass')->count();
        $this->failed_items = $this->items()->where('result', 'fail')->count();
        $this->na_items = $this->items()->where('result', 'na')->count();
        
        if ($this->failed_items > 0) {
            $this->status = 'failed';
        } elseif ($this->passed_items + $this->na_items >= $this->total_items) {
            $this->status = 'completed';
        }
        
        $this->save();
        $this->system->updateCompletion();
    }

    public function getPassRateAttribute(): float
    {
        $testable = $this->total_items - $this->na_items;
        return $testable > 0 ? round(($this->passed_items / $testable) * 100, 2) : 0;
    }

    public function scopePending($query)
    {
        return $query->where('status', 'not_started');
    }

    public function scopeFailed($query)
    {
        return $query->where('status', 'failed');
    }
}
