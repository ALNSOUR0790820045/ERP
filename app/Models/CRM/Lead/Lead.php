<?php

namespace App\Models\CRM\Lead;

use App\Models\User;
use App\Models\Customer;
use App\Models\Opportunity;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Lead extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'lead_number',
        'company_name',
        'contact_name',
        'contact_title',
        'email',
        'phone',
        'mobile',
        'website',
        'industry',
        'company_size',
        'address',
        'city',
        'country',
        'source_id',
        'campaign_id',
        'status',
        'rating',
        'score',
        'estimated_value',
        'requirements',
        'notes',
        'assigned_to',
        'first_contact_at',
        'last_contact_at',
        'qualified_at',
        'converted_at',
        'converted_customer_id',
        'converted_opportunity_id',
        'created_by',
    ];

    protected $casts = [
        'score' => 'integer',
        'estimated_value' => 'decimal:2',
        'first_contact_at' => 'datetime',
        'last_contact_at' => 'datetime',
        'qualified_at' => 'datetime',
        'converted_at' => 'datetime',
    ];

    // Boot
    protected static function boot()
    {
        parent::boot();
        
        static::creating(function ($lead) {
            if (empty($lead->lead_number)) {
                $lead->lead_number = 'LD-' . date('Y') . '-' . str_pad(static::max('id') + 1, 6, '0', STR_PAD_LEFT);
            }
        });
    }

    // العلاقات
    public function source(): BelongsTo
    {
        return $this->belongsTo(LeadSource::class, 'source_id');
    }

    public function assignee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function convertedCustomer(): BelongsTo
    {
        return $this->belongsTo(Customer::class, 'converted_customer_id');
    }

    public function convertedOpportunity(): BelongsTo
    {
        return $this->belongsTo(Opportunity::class, 'converted_opportunity_id');
    }

    public function assignments(): HasMany
    {
        return $this->hasMany(LeadAssignment::class);
    }

    public function conversion(): HasOne
    {
        return $this->hasOne(LeadConversion::class);
    }

    // Scopes
    public function scopeNew($query)
    {
        return $query->where('status', 'new');
    }

    public function scopeQualified($query)
    {
        return $query->where('status', 'qualified');
    }

    public function scopeConverted($query)
    {
        return $query->where('status', 'converted');
    }

    public function scopeHot($query)
    {
        return $query->where('rating', 'hot');
    }

    public function scopeAssignedTo($query, int $userId)
    {
        return $query->where('assigned_to', $userId);
    }

    public function scopeWithHighScore($query, int $minScore = 70)
    {
        return $query->where('score', '>=', $minScore);
    }

    // Methods
    public function calculateScore(): int
    {
        $score = 0;
        $rules = LeadScoringRule::active()->orderBy('priority')->get();
        
        foreach ($rules as $rule) {
            $fieldValue = $this->{$rule->field_name};
            
            if ($this->matchesRule($fieldValue, $rule->operator, $rule->field_value)) {
                $score += $rule->points;
            }
        }
        
        $this->update(['score' => $score]);
        return $score;
    }

    private function matchesRule($fieldValue, string $operator, $ruleValue): bool
    {
        return match($operator) {
            'equals' => $fieldValue == $ruleValue,
            'not_equals' => $fieldValue != $ruleValue,
            'contains' => str_contains($fieldValue ?? '', $ruleValue),
            'greater_than' => $fieldValue > $ruleValue,
            'less_than' => $fieldValue < $ruleValue,
            'is_empty' => empty($fieldValue),
            'is_not_empty' => !empty($fieldValue),
            default => false,
        };
    }

    public function qualify(): void
    {
        $this->update([
            'status' => 'qualified',
            'qualified_at' => now(),
        ]);
    }

    public function convert(int $customerId, ?int $opportunityId = null): LeadConversion
    {
        $this->update([
            'status' => 'converted',
            'converted_at' => now(),
            'converted_customer_id' => $customerId,
            'converted_opportunity_id' => $opportunityId,
        ]);

        return LeadConversion::create([
            'lead_id' => $this->id,
            'customer_id' => $customerId,
            'opportunity_id' => $opportunityId,
            'converted_at' => now(),
            'converted_by' => auth()->id(),
            'lead_value' => $this->estimated_value,
            'days_to_convert' => $this->created_at->diffInDays(now()),
        ]);
    }

    public function assignTo(int $userId, ?string $reason = null): LeadAssignment
    {
        // إغلاق التعيين السابق
        $this->assignments()->where('status', 'active')->update(['status' => 'reassigned']);
        
        $this->update(['assigned_to' => $userId]);
        
        return LeadAssignment::create([
            'lead_id' => $this->id,
            'assigned_to' => $userId,
            'assigned_by' => auth()->id(),
            'assigned_at' => now(),
            'assignment_reason' => $reason,
            'status' => 'active',
        ]);
    }

    public function recordContact(): void
    {
        $this->update([
            'last_contact_at' => now(),
            'first_contact_at' => $this->first_contact_at ?? now(),
            'status' => $this->status === 'new' ? 'contacted' : $this->status,
        ]);
    }

    public function isHot(): bool
    {
        return $this->rating === 'hot';
    }

    public function isConverted(): bool
    {
        return $this->status === 'converted';
    }
}
