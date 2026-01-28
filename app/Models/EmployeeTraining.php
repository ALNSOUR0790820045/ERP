<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EmployeeTraining extends Model
{
    use HasFactory;

    protected $fillable = [
        'employee_id', 'training_name', 'training_type', 'provider',
        'start_date', 'end_date', 'duration_hours', 'location',
        'cost', 'currency_id', 'certificate_obtained',
        'certificate_number', 'certificate_expiry', 'score',
        'status', 'feedback', 'notes',
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
        'certificate_expiry' => 'date',
        'cost' => 'decimal:3',
        'duration_hours' => 'decimal:2',
        'score' => 'decimal:2',
        'certificate_obtained' => 'boolean',
    ];

    public function employee(): BelongsTo { return $this->belongsTo(Employee::class); }
    public function currency(): BelongsTo { return $this->belongsTo(Currency::class); }

    public function scopeCompleted($query) { return $query->where('status', 'completed'); }
    public function scopeOngoing($query) { return $query->where('status', 'ongoing'); }
}
