<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Opportunity extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'opportunity_number',
        'opportunity_name',
        'customer_id',
        'contact_id',
        'source',
        'type',
        'estimated_value',
        'probability',
        'expected_close_date',
        'stage',
        'assigned_to',
        'description',
        'lost_reason',
        'tender_id',
        'contract_id',
        'created_by',
    ];

    protected $casts = [
        'expected_close_date' => 'date',
        'estimated_value' => 'decimal:2',
    ];

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function contact(): BelongsTo
    {
        return $this->belongsTo(CustomerContact::class, 'contact_id');
    }

    public function tender(): BelongsTo
    {
        return $this->belongsTo(Tender::class);
    }

    public function contract(): BelongsTo
    {
        return $this->belongsTo(Contract::class);
    }

    public function assignedTo(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function activities()
    {
        return $this->morphMany(CrmActivity::class, 'related');
    }

    public function quotations(): HasMany
    {
        return $this->hasMany(Quotation::class);
    }

    public function getWeightedValueAttribute(): float
    {
        return $this->estimated_value * ($this->probability / 100);
    }

    public function getStageNameAttribute(): string
    {
        return match($this->stage) {
            'identification' => 'تحديد',
            'qualification' => 'تأهيل',
            'proposal' => 'عرض',
            'negotiation' => 'تفاوض',
            'won' => 'فوز',
            'lost' => 'خسارة',
            default => $this->stage,
        };
    }

    public static function generateNumber(): string
    {
        $year = date('Y');
        $last = static::whereYear('created_at', $year)->count() + 1;
        return "OPP-{$year}-" . str_pad($last, 5, '0', STR_PAD_LEFT);
    }
}
