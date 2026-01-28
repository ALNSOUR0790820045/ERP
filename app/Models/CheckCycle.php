<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CheckCycle extends Model
{
    use HasFactory;

    protected $fillable = [
        'check_id', 'action', 'action_date', 'from_status', 'to_status',
        'bank_account_id', 'notes', 'created_by',
    ];

    protected $casts = [
        'action_date' => 'datetime',
    ];

    public function check(): BelongsTo { return $this->belongsTo(Check::class); }
    public function bankAccount(): BelongsTo { return $this->belongsTo(BankAccount::class); }
    public function creator(): BelongsTo { return $this->belongsTo(User::class, 'created_by'); }
}
