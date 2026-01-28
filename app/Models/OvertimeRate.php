<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class OvertimeRate extends Model
{
    use HasFactory;

    protected $fillable = [
        'name', 'code', 'overtime_type', 'multiplier',
        'effective_from', 'effective_to', 'is_active', 'notes',
    ];

    protected $casts = [
        'multiplier' => 'decimal:2',
        'effective_from' => 'date',
        'effective_to' => 'date',
        'is_active' => 'boolean',
    ];

    public function overtimes()
    {
        return $this->hasMany(Overtime::class);
    }

    public function scopeActive($query) { return $query->where('is_active', true); }
}
