<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class MaterialReturn extends Model
{
    use HasFactory;

    protected $fillable = [
        'return_number', 'warehouse_id', 'project_id', 'supplier_id',
        'return_type', 'return_date', 'reason', 'total_value',
        'status', 'returned_by', 'received_by', 'approved_by',
    ];

    protected $casts = [
        'return_date' => 'date',
        'total_value' => 'decimal:3',
    ];

    public function warehouse(): BelongsTo { return $this->belongsTo(Warehouse::class); }
    public function project(): BelongsTo { return $this->belongsTo(Project::class); }
    public function supplier(): BelongsTo { return $this->belongsTo(Supplier::class); }
    public function returner(): BelongsTo { return $this->belongsTo(User::class, 'returned_by'); }
    public function receiver(): BelongsTo { return $this->belongsTo(User::class, 'received_by'); }
    public function approver(): BelongsTo { return $this->belongsTo(User::class, 'approved_by'); }
    public function items(): HasMany { return $this->hasMany(MaterialReturnItem::class); }

    public function scopePending($query) { return $query->where('status', 'pending'); }
    public function scopeCompleted($query) { return $query->where('status', 'completed'); }
}
