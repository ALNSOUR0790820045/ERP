<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * تفويض سير العمل
 * Workflow Delegation
 */
class WorkflowDelegation extends Model
{
    use HasFactory;

    protected $fillable = [
        'delegator_id',
        'delegate_id',
        'workflow_id',
        'start_date',
        'end_date',
        'reason',
        'is_active',
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
        'is_active' => 'boolean',
    ];

    // العلاقات
    public function delegator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'delegator_id');
    }

    public function delegate(): BelongsTo
    {
        return $this->belongsTo(User::class, 'delegate_id');
    }

    public function workflow(): BelongsTo
    {
        return $this->belongsTo(WorkflowDefinition::class, 'workflow_id');
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('is_active', true)
            ->where('start_date', '<=', now())
            ->where('end_date', '>=', now());
    }

    public function scopeForDelegator($query, $userId)
    {
        return $query->where('delegator_id', $userId);
    }

    // Methods
    /**
     * البحث عن تفويض نشط
     */
    public static function findActive($delegatorId, $workflowId = null): ?self
    {
        $query = static::active()->forDelegator($delegatorId);
        
        if ($workflowId) {
            $query->where(function ($q) use ($workflowId) {
                $q->whereNull('workflow_id')
                  ->orWhere('workflow_id', $workflowId);
            });
        }
        
        return $query->first();
    }

    /**
     * التحقق من صلاحية التفويض
     */
    public function isValid(): bool
    {
        return $this->is_active 
            && $this->start_date->lte(now()) 
            && $this->end_date->gte(now());
    }
}
