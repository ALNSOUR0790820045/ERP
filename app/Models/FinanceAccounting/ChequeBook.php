<?php

namespace App\Models\FinanceAccounting;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ChequeBook extends Model
{
    use HasFactory;

    protected $fillable = [
        'bank_account_id',
        'book_number',
        'series_prefix',
        'start_number',
        'end_number',
        'current_number',
        'total_cheques',
        'used_cheques',
        'cancelled_cheques',
        'received_date',
        'expiry_date',
        'status',
        'notes',
        'created_by',
    ];

    protected $casts = [
        'received_date' => 'date',
        'expiry_date' => 'date',
    ];

    public function bankAccount(): BelongsTo
    {
        return $this->belongsTo(BankAccount::class);
    }

    public function chequesIssued(): HasMany
    {
        return $this->hasMany(ChequeIssued::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function getNextChequeNumber(): ?string
    {
        if ($this->current_number > $this->end_number) {
            return null;
        }
        return $this->series_prefix . str_pad($this->current_number, 6, '0', STR_PAD_LEFT);
    }

    public function incrementChequeNumber(): void
    {
        $this->current_number++;
        $this->used_cheques++;
        
        if ($this->current_number > $this->end_number) {
            $this->status = 'completed';
        }
        
        $this->save();
    }

    public function getRemainingChequesAttribute(): int
    {
        return max(0, $this->end_number - $this->current_number + 1);
    }

    public function getUsagePercentageAttribute(): float
    {
        return $this->total_cheques > 0 
            ? round(($this->used_cheques / $this->total_cheques) * 100, 2) 
            : 0;
    }
}
