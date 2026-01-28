<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ScrapDisposal extends Model
{
    use HasFactory;

    protected $fillable = [
        'disposal_number', 'warehouse_id', 'disposal_date', 'disposal_method',
        'total_value', 'recovery_value', 'buyer_name', 'notes',
        'status', 'approved_by', 'created_by',
    ];

    protected $casts = [
        'disposal_date' => 'date',
        'total_value' => 'decimal:3',
        'recovery_value' => 'decimal:3',
    ];

    public function warehouse(): BelongsTo { return $this->belongsTo(Warehouse::class); }
    public function approver(): BelongsTo { return $this->belongsTo(User::class, 'approved_by'); }
    public function creator(): BelongsTo { return $this->belongsTo(User::class, 'created_by'); }

    public function scopeApproved($query) { return $query->where('status', 'approved'); }
    public function scopeCompleted($query) { return $query->where('status', 'completed'); }
}
