<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SupplierEvaluation extends Model
{
    protected $fillable = [
        'supplier_id', 'project_id', 'purchase_order_id', 'evaluation_date',
        'quality_score', 'delivery_score', 'price_score', 'service_score',
        'overall_score', 'comments', 'evaluated_by',
    ];

    protected $casts = [
        'evaluation_date' => 'date',
        'overall_score' => 'decimal:2',
    ];

    public function supplier(): BelongsTo { return $this->belongsTo(Supplier::class); }
    public function project(): BelongsTo { return $this->belongsTo(Project::class); }
    public function evaluator(): BelongsTo { return $this->belongsTo(User::class, 'evaluated_by'); }
}
