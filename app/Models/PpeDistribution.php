<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PpeDistribution extends Model
{
    protected $fillable = [
        'project_id', 'employee_id', 'ppe_item_id', 'issue_date',
        'quantity', 'expiry_date', 'return_date', 'remarks',
    ];

    protected $casts = [
        'issue_date' => 'date',
        'expiry_date' => 'date',
        'return_date' => 'date',
    ];

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function ppeItem(): BelongsTo
    {
        return $this->belongsTo(PpeItem::class);
    }
}
