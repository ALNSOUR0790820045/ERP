<?php

namespace App\Models\Engineering\Commissioning;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StartupProcedure extends Model
{
    protected $fillable = [
        'system_id',
        'procedure_number',
        'name',
        'description',
        'procedure_type',
        'steps',
        'required_resources',
        'safety_precautions',
        'status',
        'prepared_by',
        'approved_by',
        'approved_at',
        'executed_at',
        'executed_by',
        'execution_notes',
    ];

    protected $casts = [
        'steps' => 'array',
        'required_resources' => 'array',
        'safety_precautions' => 'array',
        'approved_at' => 'datetime',
        'executed_at' => 'datetime',
    ];

    public function system(): BelongsTo
    {
        return $this->belongsTo(CommissioningSystem::class, 'system_id');
    }

    public function preparer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'prepared_by');
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function executor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'executed_by');
    }

    public function approve(int $userId): void
    {
        $this->update([
            'status' => 'approved',
            'approved_by' => $userId,
            'approved_at' => now(),
        ]);
    }

    public function execute(int $userId, string $notes = null): void
    {
        $this->update([
            'status' => 'executed',
            'executed_by' => $userId,
            'executed_at' => now(),
            'execution_notes' => $notes,
        ]);
    }

    public function getStepCount(): int
    {
        return count($this->steps ?? []);
    }

    public function scopeApproved($query)
    {
        return $query->where('status', 'approved');
    }

    public function scopeByType($query, string $type)
    {
        return $query->where('procedure_type', $type);
    }
}
