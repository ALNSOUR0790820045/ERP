<?php

namespace App\Models\Engineering\PunchList;

use App\Models\Project;
use App\Models\Contract;
use App\Models\User;
use App\Models\Supplier;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PunchList extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'list_number',
        'project_id',
        'contract_id',
        'name',
        'description',
        'area',
        'discipline',
        'list_type',
        'walkthrough_date',
        'due_date',
        'created_by',
        'responsible_contractor_id',
        'total_items',
        'completed_items',
        'completion_percentage',
        'status',
        'issued_at',
        'closed_at',
    ];

    protected $casts = [
        'walkthrough_date' => 'date',
        'due_date' => 'date',
        'total_items' => 'integer',
        'completed_items' => 'integer',
        'completion_percentage' => 'decimal:2',
        'issued_at' => 'datetime',
        'closed_at' => 'datetime',
    ];

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function contract(): BelongsTo
    {
        return $this->belongsTo(Contract::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function responsibleContractor(): BelongsTo
    {
        return $this->belongsTo(Supplier::class, 'responsible_contractor_id');
    }

    public function items(): HasMany
    {
        return $this->hasMany(PunchItem::class);
    }

    public function updateCompletion(): void
    {
        $this->total_items = $this->items()->count();
        $this->completed_items = $this->items()->whereIn('status', ['completed', 'verified'])->count();
        $this->completion_percentage = $this->total_items > 0 
            ? round(($this->completed_items / $this->total_items) * 100, 2) 
            : 0;
        $this->save();
    }

    public function scopeOpen($query)
    {
        return $query->whereNotIn('status', ['closed']);
    }

    public function scopeByProject($query, $projectId)
    {
        return $query->where('project_id', $projectId);
    }
}
