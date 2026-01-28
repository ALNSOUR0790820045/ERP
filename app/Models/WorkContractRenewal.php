<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WorkContractRenewal extends Model
{
    use HasFactory;

    protected $fillable = [
        'work_contract_id', 'renewal_number', 'previous_end_date', 'new_end_date',
        'previous_salary', 'new_salary', 'salary_change_percentage',
        'renewal_date', 'renewal_reason', 'approved_by', 'notes',
    ];

    protected $casts = [
        'previous_end_date' => 'date',
        'new_end_date' => 'date',
        'renewal_date' => 'date',
        'previous_salary' => 'decimal:3',
        'new_salary' => 'decimal:3',
        'salary_change_percentage' => 'decimal:2',
    ];

    public function workContract(): BelongsTo { return $this->belongsTo(WorkContract::class); }
    public function approver(): BelongsTo { return $this->belongsTo(User::class, 'approved_by'); }
}
