<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class GoodsIssueItem extends Model
{
    protected $fillable = [
        'issue_id', 'material_id', 'requested_qty', 'issued_qty', 'unit_cost',
    ];

    protected $casts = [
        'requested_qty' => 'decimal:4',
        'issued_qty' => 'decimal:4',
        'unit_cost' => 'decimal:3',
    ];

    public function issue(): BelongsTo
    {
        return $this->belongsTo(GoodsIssue::class, 'issue_id');
    }

    public function material(): BelongsTo
    {
        return $this->belongsTo(Material::class);
    }
}
