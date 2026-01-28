<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ContractSuspension extends Model
{
    use HasFactory;

    protected $fillable = [
        'contract_id', 'suspension_number', 'suspension_type', 'reason',
        'start_date', 'planned_end_date', 'actual_end_date', 'duration_days',
        'impact_description', 'cost_impact', 'time_impact_days',
        'status', 'issued_by', 'lifted_by', 'lifted_at', 'notes',
    ];

    protected $casts = [
        'start_date' => 'date',
        'planned_end_date' => 'date',
        'actual_end_date' => 'date',
        'lifted_at' => 'datetime',
        'cost_impact' => 'decimal:3',
    ];

    public function contract(): BelongsTo { return $this->belongsTo(Contract::class); }
    public function issuer(): BelongsTo { return $this->belongsTo(User::class, 'issued_by'); }
    public function lifter(): BelongsTo { return $this->belongsTo(User::class, 'lifted_by'); }

    public function scopeActive($query) { return $query->where('status', 'active'); }
    public function scopeLifted($query) { return $query->where('status', 'lifted'); }
}
