<?php

namespace App\Models\Engineering\Procurement;

use App\Models\Project;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProjectPurchaseRequest extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'request_number',
        'project_id',
        'wbs_id',
        'cost_code_id',
        'title',
        'description',
        'request_type',
        'urgency',
        'required_date',
        'delivery_location',
        'estimated_value',
        'currency',
        'status',
        'requested_by',
        'approved_by',
        'approved_at',
    ];

    protected $casts = [
        'required_date' => 'date',
        'estimated_value' => 'decimal:2',
        'approved_at' => 'datetime',
    ];

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function wbs(): BelongsTo
    {
        return $this->belongsTo(\App\Models\ProjectWbs::class, 'wbs_id');
    }

    public function costCode(): BelongsTo
    {
        return $this->belongsTo(\App\Models\ProjectCost::class, 'cost_code_id');
    }

    public function requester(): BelongsTo
    {
        return $this->belongsTo(User::class, 'requested_by');
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function items(): HasMany
    {
        return $this->hasMany(ProjectPurchaseRequestItem::class, 'request_id');
    }

    public function calculateTotal(): float
    {
        return $this->items()->sum('total_price') ?? 0;
    }

    public function submit(): void
    {
        $this->estimated_value = $this->calculateTotal();
        $this->status = 'submitted';
        $this->save();
    }

    public function approve(int $userId): void
    {
        $this->update([
            'status' => 'approved',
            'approved_by' => $userId,
            'approved_at' => now(),
        ]);
    }

    public static function generateNumber(int $projectId): string
    {
        $count = self::where('project_id', $projectId)->count() + 1;
        return sprintf('PPR-%d-%04d', $projectId, $count);
    }

    public function scopePending($query)
    {
        return $query->whereIn('status', ['draft', 'submitted']);
    }

    public function scopeUrgent($query)
    {
        return $query->whereIn('urgency', ['urgent', 'critical']);
    }

    public function scopeByType($query, string $type)
    {
        return $query->where('request_type', $type);
    }
}
