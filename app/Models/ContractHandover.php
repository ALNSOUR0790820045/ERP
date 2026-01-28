<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ContractHandover extends Model
{
    use HasFactory;

    protected $fillable = [
        'contract_id', 'project_id', 'handover_type', 'handover_date',
        'handover_number', 'description', 'items_handed_over',
        'pending_items', 'snag_list', 'documentation_list',
        'warranties', 'spare_parts', 'as_built_drawings',
        'operation_manuals', 'status', 'handed_over_by', 'received_by',
        'witnessed_by', 'notes',
    ];

    protected $casts = [
        'handover_date' => 'date',
        'items_handed_over' => 'array',
        'pending_items' => 'array',
        'snag_list' => 'array',
        'documentation_list' => 'array',
        'warranties' => 'array',
        'spare_parts' => 'array',
        'as_built_drawings' => 'boolean',
        'operation_manuals' => 'boolean',
    ];

    public function contract(): BelongsTo { return $this->belongsTo(Contract::class); }
    public function project(): BelongsTo { return $this->belongsTo(Project::class); }
    public function handedOverBy(): BelongsTo { return $this->belongsTo(User::class, 'handed_over_by'); }
    public function receivedBy(): BelongsTo { return $this->belongsTo(User::class, 'received_by'); }
    public function witnessedBy(): BelongsTo { return $this->belongsTo(User::class, 'witnessed_by'); }

    public function scopeProvisional($query) { return $query->where('handover_type', 'provisional'); }
    public function scopeFinal($query) { return $query->where('handover_type', 'final'); }
}
