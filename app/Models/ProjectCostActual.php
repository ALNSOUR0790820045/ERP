<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProjectCostActual extends Model
{
    use HasFactory;

    protected $fillable = [
        'project_cost_id', 'transaction_date', 'transaction_type',
        'reference_type', 'reference_id', 'description',
        'quantity', 'unit_rate', 'amount', 'currency_id',
        'exchange_rate', 'notes', 'created_by',
    ];

    protected $casts = [
        'transaction_date' => 'date',
        'quantity' => 'decimal:4',
        'unit_rate' => 'decimal:4',
        'amount' => 'decimal:3',
        'exchange_rate' => 'decimal:6',
    ];

    public function projectCost(): BelongsTo { return $this->belongsTo(ProjectCost::class); }
    public function currency(): BelongsTo { return $this->belongsTo(Currency::class); }
    public function creator(): BelongsTo { return $this->belongsTo(User::class, 'created_by'); }
}
