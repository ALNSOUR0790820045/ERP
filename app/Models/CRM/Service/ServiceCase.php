<?php

namespace App\Models\CRM\Service;

use App\Models\User;
use App\Models\Customer;
use App\Models\CustomerContact;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ServiceCase extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'case_number',
        'customer_id',
        'contact_id',
        'category_id',
        'service_contract_id',
        'subject',
        'description',
        'priority',
        'status',
        'channel',
        'sla_policy_id',
        'sla_first_response_due',
        'sla_resolution_due',
        'first_responded_at',
        'resolved_at',
        'closed_at',
        'sla_first_response_breached',
        'sla_resolution_breached',
        'assigned_to',
        'escalated_to',
        'escalation_level',
        'resolution',
        'customer_satisfaction',
        'feedback',
        'created_by',
    ];

    protected $casts = [
        'sla_first_response_due' => 'datetime',
        'sla_resolution_due' => 'datetime',
        'first_responded_at' => 'datetime',
        'resolved_at' => 'datetime',
        'closed_at' => 'datetime',
        'sla_first_response_breached' => 'boolean',
        'sla_resolution_breached' => 'boolean',
        'escalation_level' => 'integer',
        'customer_satisfaction' => 'integer',
    ];

    // Boot
    protected static function boot()
    {
        parent::boot();
        
        static::creating(function ($case) {
            if (empty($case->case_number)) {
                $case->case_number = 'CS-' . date('Y') . '-' . str_pad(static::max('id') + 1, 6, '0', STR_PAD_LEFT);
            }
            
            // تعيين SLA تلقائياً
            if (!$case->sla_policy_id) {
                $case->applySlaPolicy();
            }
        });
    }

    // العلاقات
    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function contact(): BelongsTo
    {
        return $this->belongsTo(CustomerContact::class, 'contact_id');
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(CaseCategory::class, 'category_id');
    }

    public function contract(): BelongsTo
    {
        return $this->belongsTo(ServiceContract::class, 'service_contract_id');
    }

    public function slaPolicy(): BelongsTo
    {
        return $this->belongsTo(SlaPolicy::class, 'sla_policy_id');
    }

    public function assignee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    public function escalatedTo(): BelongsTo
    {
        return $this->belongsTo(User::class, 'escalated_to');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function comments(): HasMany
    {
        return $this->hasMany(CaseComment::class, 'case_id');
    }

    public function breaches(): HasMany
    {
        return $this->hasMany(SlaBreach::class, 'case_id');
    }

    // Scopes
    public function scopeOpen($query)
    {
        return $query->whereNotIn('status', ['closed', 'resolved']);
    }

    public function scopeClosed($query)
    {
        return $query->whereIn('status', ['closed', 'resolved']);
    }

    public function scopeForCustomer($query, int $customerId)
    {
        return $query->where('customer_id', $customerId);
    }

    public function scopeAssignedTo($query, int $userId)
    {
        return $query->where('assigned_to', $userId);
    }

    public function scopeByPriority($query, string $priority)
    {
        return $query->where('priority', $priority);
    }

    public function scopeBreached($query)
    {
        return $query->where(function ($q) {
            $q->where('sla_first_response_breached', true)
              ->orWhere('sla_resolution_breached', true);
        });
    }

    public function scopeOverdue($query)
    {
        return $query->open()
            ->where(function ($q) {
                $q->where('sla_resolution_due', '<', now())
                  ->where('sla_resolution_breached', false);
            });
    }

    // Methods
    private function applySlaPolicy(): void
    {
        // البحث عن SLA من العقد أولاً
        if ($this->service_contract_id) {
            $contract = ServiceContract::find($this->service_contract_id);
            if ($contract?->sla_policy_id) {
                $this->sla_policy_id = $contract->sla_policy_id;
            }
        }
        
        // أو استخدام SLA افتراضي
        if (!$this->sla_policy_id) {
            $policy = SlaPolicy::getForPriority($this->priority);
            $this->sla_policy_id = $policy?->id;
        }
        
        // حساب مواعيد SLA
        if ($this->sla_policy_id) {
            $policy = SlaPolicy::find($this->sla_policy_id);
            $this->sla_first_response_due = $policy->calculateFirstResponseDue(now());
            $this->sla_resolution_due = $policy->calculateResolutionDue(now());
        }
    }

    public function respond(): void
    {
        if ($this->first_responded_at) return;
        
        $this->first_responded_at = now();
        
        if ($this->sla_first_response_due && now()->gt($this->sla_first_response_due)) {
            $this->sla_first_response_breached = true;
            $this->recordSlaBreach('first_response');
        }
        
        $this->save();
    }

    public function resolve(string $resolution): void
    {
        $this->resolution = $resolution;
        $this->resolved_at = now();
        $this->status = 'resolved';
        
        if ($this->sla_resolution_due && now()->gt($this->sla_resolution_due)) {
            $this->sla_resolution_breached = true;
            $this->recordSlaBreach('resolution');
        }
        
        $this->save();
    }

    public function close(): void
    {
        $this->status = 'closed';
        $this->closed_at = now();
        $this->save();
    }

    public function reopen(): void
    {
        $this->status = 'reopened';
        $this->closed_at = null;
        $this->resolved_at = null;
        $this->save();
    }

    public function escalate(int $userId): void
    {
        $this->escalated_to = $userId;
        $this->escalation_level++;
        $this->status = 'in_progress';
        $this->save();
        
        $this->addComment("تم تصعيد الحالة إلى المستوى {$this->escalation_level}", 'system');
    }

    public function assignTo(int $userId): void
    {
        $this->assigned_to = $userId;
        $this->status = 'assigned';
        $this->save();
    }

    public function addComment(string $comment, string $type = 'internal', bool $isResolution = false): CaseComment
    {
        return $this->comments()->create([
            'comment' => $comment,
            'comment_type' => $type,
            'is_resolution' => $isResolution,
            'created_by' => auth()->id(),
        ]);
    }

    private function recordSlaBreach(string $type): void
    {
        SlaBreach::create([
            'case_id' => $this->id,
            'sla_policy_id' => $this->sla_policy_id,
            'breach_type' => $type,
            'due_at' => $type === 'first_response' ? $this->sla_first_response_due : $this->sla_resolution_due,
            'breached_at' => now(),
            'breach_minutes' => $type === 'first_response' 
                ? now()->diffInMinutes($this->sla_first_response_due)
                : now()->diffInMinutes($this->sla_resolution_due),
        ]);
    }

    public function setSatisfaction(int $score, ?string $feedback = null): void
    {
        $this->customer_satisfaction = $score;
        $this->feedback = $feedback;
        $this->save();
    }

    public function getAgeInHours(): int
    {
        return $this->created_at->diffInHours(now());
    }

    public function isOverdue(): bool
    {
        return !$this->isClosed() && 
               $this->sla_resolution_due && 
               now()->gt($this->sla_resolution_due);
    }

    public function isClosed(): bool
    {
        return in_array($this->status, ['closed', 'resolved']);
    }
}
