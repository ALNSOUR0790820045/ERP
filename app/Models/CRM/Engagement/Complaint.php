<?php

namespace App\Models\CRM\Engagement;

use App\Models\User;
use App\Models\Customer;
use App\Models\CustomerContact;
use App\Models\CRM\Service\ServiceCase;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Complaint extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'complaint_number',
        'customer_id',
        'contact_id',
        'case_id',
        'complaint_type',
        'severity',
        'subject',
        'description',
        'related_documents',
        'status',
        'root_cause',
        'corrective_action',
        'preventive_action',
        'assigned_to',
        'resolved_by',
        'resolved_at',
        'compensation_amount',
        'customer_satisfied',
    ];

    protected $casts = [
        'related_documents' => 'array',
        'resolved_at' => 'datetime',
        'compensation_amount' => 'decimal:2',
        'customer_satisfied' => 'boolean',
    ];

    // Boot
    protected static function boot()
    {
        parent::boot();
        
        static::creating(function ($complaint) {
            if (empty($complaint->complaint_number)) {
                $complaint->complaint_number = 'CMP-' . date('Y') . '-' . str_pad(static::max('id') + 1, 6, '0', STR_PAD_LEFT);
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

    public function case(): BelongsTo
    {
        return $this->belongsTo(ServiceCase::class, 'case_id');
    }

    public function assignee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    public function resolver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'resolved_by');
    }

    // Scopes
    public function scopeOpen($query)
    {
        return $query->whereNotIn('status', ['resolved', 'closed']);
    }

    public function scopeClosed($query)
    {
        return $query->whereIn('status', ['resolved', 'closed']);
    }

    public function scopeByType($query, string $type)
    {
        return $query->where('complaint_type', $type);
    }

    public function scopeBySeverity($query, string $severity)
    {
        return $query->where('severity', $severity);
    }

    public function scopeCritical($query)
    {
        return $query->where('severity', 'critical');
    }

    public function scopeForCustomer($query, int $customerId)
    {
        return $query->where('customer_id', $customerId);
    }

    public function scopeEscalated($query)
    {
        return $query->where('status', 'escalated');
    }

    // Methods
    public function assignTo(int $userId): void
    {
        $this->update([
            'assigned_to' => $userId,
            'status' => 'investigating',
        ]);
    }

    public function escalate(): void
    {
        $this->update(['status' => 'escalated']);
    }

    public function resolve(string $rootCause, string $correctiveAction, ?string $preventiveAction = null): void
    {
        $this->update([
            'status' => 'resolved',
            'root_cause' => $rootCause,
            'corrective_action' => $correctiveAction,
            'preventive_action' => $preventiveAction,
            'resolved_by' => auth()->id(),
            'resolved_at' => now(),
        ]);
    }

    public function close(bool $customerSatisfied): void
    {
        $this->update([
            'status' => 'closed',
            'customer_satisfied' => $customerSatisfied,
        ]);
    }

    public function addCompensation(float $amount): void
    {
        $this->update(['compensation_amount' => $amount]);
    }

    public function isCritical(): bool
    {
        return $this->severity === 'critical';
    }

    public function isOpen(): bool
    {
        return !in_array($this->status, ['resolved', 'closed']);
    }

    public function getAgeInDays(): int
    {
        return $this->created_at->diffInDays(now());
    }

    // إحصائيات
    public static function getByTypeDistribution(int $days = 30): array
    {
        return static::where('created_at', '>=', now()->subDays($days))
            ->selectRaw('complaint_type, COUNT(*) as count')
            ->groupBy('complaint_type')
            ->pluck('count', 'complaint_type')
            ->toArray();
    }

    public static function getResolutionRate(int $days = 30): float
    {
        $total = static::where('created_at', '>=', now()->subDays($days))->count();
        $resolved = static::where('created_at', '>=', now()->subDays($days))
            ->whereIn('status', ['resolved', 'closed'])
            ->count();
            
        return $total > 0 ? ($resolved / $total) * 100 : 0;
    }

    public static function getSatisfactionRate(int $days = 30): float
    {
        $closed = static::where('created_at', '>=', now()->subDays($days))
            ->where('status', 'closed')
            ->whereNotNull('customer_satisfied')
            ->get();
            
        $total = $closed->count();
        $satisfied = $closed->where('customer_satisfied', true)->count();
        
        return $total > 0 ? ($satisfied / $total) * 100 : 0;
    }
}
